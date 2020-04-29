<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\User;

/**
 * App\Notification
 *
 * @property-read mixed $id
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 * @method static \Illuminate\Database\Query\Builder|Notification where($value)
 * @mixin Eloquent
 */
class Notification extends Model
{
    use UsesUuid;
    public static function new($title, $type, $message, $server_id = null, $extension_id = null, $level = 0)
    {
        // Create a notification object and fill values.
        // Before we return the notification, check if it's urgent. If so, send an email.
        return Notification::create([
            "user_id" => auth()->id(),
            "title" => $title,
            "type" => $type,
            "message" => $message,
            "server_id" => $server_id,
            "extension_id" => $extension_id,
            "level" => $level,
            "read" => false,
        ]);
    }
    public static function send($title, $type, $message, $user_id, $server_id = null, $extension_id = null, $level = 0)
    {
        // Create a notification object and fill values.
        return Notification::create([
            "user_id" => $user_id,
            "title" => $title,
            "type" => $type,
            "message" => $message,
            "server_id" => $server_id,
            "extension_id" => $extension_id,
            "level" => $level,
            "read" => false,
        ]);
    }
}
