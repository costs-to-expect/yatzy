<?php

namespace App\Providers;

use App\Auth\Guard\Api\Guard;
use App\Auth\Guard\Api\UserProvider;
use Illuminate\Container\Container;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::extend('api', static function (Container $app) {
            $config = Config::get('app.config');
            $auth_config = [
                'cookie_bearer' => $config['cookie_bearer'],
                'cookie_user' => $config['cookie_user']
            ];

            return new Guard(new UserProvider($auth_config), $auth_config, $app['request']);
        });

        $this->registerPolicies();
    }
}
