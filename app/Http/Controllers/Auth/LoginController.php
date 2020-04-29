<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Server;
use App\LdapRestriction;
use App\UserSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\User;
use App\RoleMapping;
use App\RoleUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Class LoginController
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function authenticated(Request $request, $user)
    {
        $user->update([
            "last_login_at" => Carbon::now()->toDateTimeString(),
            "last_login_ip" => $request->ip()
        ]);

        system_log(7, "LOGIN_SUCCESS");

        hook("login_successful", [
            "user" => $user
        ]);
    }

    public function attemptLogin(Request $request)
    {
        $credientials = (object) $this->credentials($request);

        hook('login_attempt', [
            "email" => $credientials->email,
            "password" => $credientials->password
        ]);

        $flag =  $this->guard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );

        // Will be deleted later.
        if (!$flag && config('ldap.ldap_host', false) && config('ldap.ldap_status', true)) {
            if (!config('ldap.ldap_domain', false)) {
                setBaseDn();
            }
            $guidColumn = config('ldap.ldap_guid_column', 'objectguid');
            $base_dn = config('ldap.ldap_base_dn');
            $domain = config('ldap.ldap_domain');

            $ldap_restrictions = LdapRestriction::all();
            $restrictedUsers = $ldap_restrictions->where('type', 'user')->pluck('name')->all();
            $restrictedGroups = $ldap_restrictions->where('type', 'group')->pluck('name')->all();

            try {
                $ldapConnection = ldap_connect("ldaps://" . config('ldap.ldap_host'));
                ldap_set_option($ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, 10);
                ldap_set_option($ldapConnection, LDAP_OPT_TIMELIMIT, 10);
                ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldapConnection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
                $flag = ldap_bind($ldapConnection, $credientials->email . "@" . $domain, $credientials->password);
            } catch (\Exception $ex) {
                return false;
            }
            if ($flag) {
                $sr = ldap_search($ldapConnection, $base_dn, '(&(objectClass=user)(sAMAccountName=' . $credientials->email . '))', [$guidColumn, 'samaccountname', 'memberof']);
                $ldapUser = ldap_get_entries($ldapConnection, $sr);
                if (!isset($ldapUser[0][$guidColumn])) {
                    return false;
                }
                $userGroups = isset($ldapUser[0]["memberof"]) ? $ldapUser[0]["memberof"] : [];
                unset($userGroups['count']);

                if (!(((bool) $restrictedGroups) == false && ((bool) $restrictedUsers) == false)) {
                    $groupCheck = (bool) $restrictedGroups;
                    $userCheck = (bool) $restrictedUsers;
                    if ($restrictedGroups && count(array_intersect($userGroups, $restrictedGroups)) === 0) {
                        $groupCheck = false;
                    }
                    if ($restrictedUsers && !in_array(strtolower($credientials->email), $restrictedUsers)) {
                        $userCheck = false;
                    }
                    if ($groupCheck === false && $userCheck === false) {
                        return false;
                    }
                }

                $user = User::where($guidColumn, bin2hex($ldapUser[0][$guidColumn][0]))->first();
                if (!$user) {
                    $user = User::create([
                        "name" => $credientials->email,
                        "email" => $credientials->email . "@" . $domain,
                        "password" => Hash::make(str_random("16")),
                        "objectguid" => bin2hex($ldapUser[0][$guidColumn][0]),
                        "auth_type" => "ldap"
                    ]);
                } else {
                    $user->update([
                        "name" => $credientials->email,
                        "email" => $credientials->email . "@" . $domain,
                        "auth_type" => "ldap"
                    ]);
                }
                RoleUser::where('user_id', $user->id)->where('type', 'ldap')->delete();
                if (isset($ldapUser[0]["memberof"]) && $ldapUser[0]["memberof"]['count']) {
                    unset($ldapUser[0]["memberof"]['count']);
                    foreach ($ldapUser[0]["memberof"] as $row) {
                        RoleMapping::where('group_id', md5($row))->get()->map(function ($item) use ($user) {
                            RoleUser::firstOrCreate([
                                "user_id" => $user->id,
                                "role_id" => $item->role_id,
                                "type" => "ldap"
                            ]);
                        });
                    }
                }
                // Let's add user credentials to database.
                // First, find servers.
                foreach (Server::where('ip_address', trim(env('LDAP_HOST')))->get() as $server) {

                    $encKey = env('APP_KEY') . $user->id  . $server->id;
                    $encrypted = openssl_encrypt(Str::random(16) . base64_encode($credientials->email), 'aes-256-cfb8', $encKey, 0, Str::random(16));
                    UserSettings::updateOrCreate([
                        "user_id" => $user->id,
                        "server_id" => $server->id,
                        "name" => "clientUsername"
                    ], [
                        "value" => $encrypted
                    ]);
                    $encKey = env('APP_KEY') . $user->id  . $server->id;
                    $encrypted = openssl_encrypt(Str::random(16) . base64_encode($credientials->password), 'aes-256-cfb8', $encKey, 0, Str::random(16));
                    UserSettings::updateOrCreate([
                        "user_id" => $user->id,
                        "server_id" => $server->id,
                        "name" => "clientPassword"
                    ], [
                        "value" => $encrypted
                    ]);
                }
                $this->guard()->login($user, $request->filled('remember'));
                return true;
            } else {
                system_log(5, "LOGIN_FAILED");
            }
        }
        return $flag;
    }

    protected function validateLogin(Request $request)
    {
        $request->request->add([
            $this->username() => $request->liman_email_mert,
            "password" => $request->liman_password_baran
        ]);
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $credientials = (object) $this->credentials($request);
        hook('login_failed', [
            "email" => $credientials->email,
            "password" => $credientials->password
        ]);

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
