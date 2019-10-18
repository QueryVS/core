<?php

use App\User;
use App\Permission;
use App\Extension;
use App\Script;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('administrator',function (){

    // Generate Password
    do{
        $pool = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&');
        $password = substr($pool,0,10);
    }while(!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/", $password));
    $user = User::where([
        "name" => "Administrator",
        "email" => "administrator@liman.app"
    ])->first();
    if($user){
        $user->update([
            "password" => Hash::make($password)
        ]);
    }else{
        $user = new User();
        $user->fill([
            "name" => "Administrator",
            "email" => "administrator@liman.app",
            "password" => Hash::make($password),
            "status" => 1,
        ]);
    }
    $user->save();

    $this->comment("Liman MYS Administrator Kullanıcısı");
    $this->comment("Email  : administrator@liman.app");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');

Artisan::command('activate_extension {extension_name}',function ($extension_name){
    try{
        $extension_folder = env("EXTENSIONS_PATH").$extension_name;
        $ext_info = json_decode(file_get_contents($extension_folder . '/db.json'));
        $extension = Extension::where('name', $ext_info->name)->first();
        if ($extension) {
            $new = $extension;
        } else {
            $new = new Extension();
        }
        $new->fill((array)$ext_info);
        $new->save();
        if ((intval(shell_exec("grep -c '^" . clean_score($new->id) . "' /etc/passwd"))) ? false : true) {
            shell_exec('sudo useradd -r -s /bin/sh ' . clean_score($new->id));
        }
        $passPath = env('KEYS_PATH') . DIRECTORY_SEPARATOR . $new->id;
        file_put_contents($passPath,Str::random(32));
        shell_exec("sudo chown liman:" . clean_score($new->id) . " " . $passPath);
        shell_exec("sudo chmod 640 " . $passPath);

        shell_exec('sudo chown ' . clean_score($new->id) . ':liman ' . $extension_folder);
        shell_exec('sudo chmod 770 ' . $extension_folder);
        shell_exec("sudo chown -R " . clean_score($new->id) . ':liman "' . $extension_folder. '"');
        shell_exec("sudo chmod -R 770 \"" . $extension_folder ."\"");
        shell_exec("sudo chown liman:". clean_score($new->id) . " " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");
        shell_exec("sudo chmod 640 " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");
        if(is_dir($extension_folder . '/scripts/')){
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extension_folder . '/scripts/'),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
        }else {
            $files = [];
        }
        foreach ($files as $file) {
            if (!$file->isDir()) {
                if (substr($file->getFilename(), 0, 1) == "." || !Str::endsWith($file->getFilename(), ".lmns")) {
                    continue;
                }
                $filePath = $file->getRealPath();
    
                Script::readFromFile($filePath);
            }
        }
        system_log(3,"EXTENSION_ACTIVATION_SUCCESS",[
            "extension_id" => $new->id
        ]);
    }catch (Exception $exception){}
})->describe('Activate an extension');

Artisan::command('remove_extension {extension_name}',function ($extension_name){
    $extension_folder = env("EXTENSIONS_PATH").$extension_name;
    try{
        $ext_info = json_decode(file_get_contents($extension_folder . '/db.json'));
        foreach (Script::where('extensions', 'like', strtolower($ext_info->name))->get() as $script) {
            $script->delete();
        }
        $extension = Extension::where('name', $ext_info->name)->first();
        shell_exec('rm ' . env('KEYS_PATH') . DIRECTORY_SEPARATOR . $extension->id);
        shell_exec('sudo userdel ' . clean_score($extension->id));
        $extension->delete();
    }catch (Exception $exception){}
})->describe('Remove an extension');