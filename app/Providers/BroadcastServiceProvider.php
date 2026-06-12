<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

/**
 * Broadcasting service provider — Pusher real-time channel auth.
 *
 * routes/channels.php içindeki private channel kuralları sayesinde
 * sadece doğru kullanıcı kendi user.{id} kanalına subscribe olabilir;
 * senior/manager/platform.owner kanallarında ek rol kontrolü vardır.
 *
 * Broadcasting auth route'u standart Laravel auth middleware'i altında
 * çalışır — guest user broadcast yapamaz.
 */
class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        require base_path('routes/channels.php');
    }
}
