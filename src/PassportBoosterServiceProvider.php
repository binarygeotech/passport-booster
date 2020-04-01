<?php

namespace BGS\PassportBooster;

use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\ResourceServer;
use Laravel\Passport\Bridge\ScopeRepository;
use League\OAuth2\Server\AuthorizationServer;
use BGS\PassportBooster\Http\Middleware\PassportBoosterRouteChecker;
use Laravel\Passport\PassportServiceProvider as BasePassportServiceProvider;

class PassportBoosterServiceProvider extends BasePassportServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../config/passport_booster.php' => config_path('passport_booster.php'),
                ],
                'passport-booster-config'
            );
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom( __DIR__ . '/../config/passport_booster.php', 'passport_booster');

        $this->registerAuthorizationServer();
        $this->registerResourceServer();
        $this->registerGuard();
        $this->registerMiddleware();
    }

    /**
     * Register Passport Booster Middleware
     *
     * @return void
     */
    protected function registerMiddleware()
    {
        $this->app['router']->aliasMiddleware(
            'passport.guard',
            PassportBoosterRouteChecker::class
        );

        $sorted = $this->app['router']->middlewarePriority;
        $this->app['router']->middlewarePriority = array_merge(
            [
                PassportBoosterRouteChecker::class
            ],
            $sorted
        );
    }

    /**
     * Register the resource server.
     *
     * @return void
     */
    protected function registerResourceServer()
    {
        $this->app->singleton(ResourceServer::class, function () {
            return new ResourceServer(
                $this->app->make(config('passport_booster.access_token_repository')),
                $this->makeCryptKey('public'),
                $this->app->make(config('passport_booster.bearer_token_validator'))
            );
        });
    }

    /**
     * Make the authorization service instance.
     *
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    public function makeAuthorizationServer()
    {
        return new AuthorizationServer(
            $this->app->make(config('passport_booster.client_repository_bridge')),
            $this->app->make(config('passport_booster.access_token_repository')),
            $this->app->make(ScopeRepository::class),
            $this->makeCryptKey('private'),
            app('encrypter')->getKey()
        );
    }

    /**
     * Register the token guard.
     *
     * @return void
     */
    protected function registerGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('passport', function ($app, $name, array $config) {
                return tap($this->makeGuard($config), function ($guard) {
                    $this->app->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function makeGuard(array $config)
    {
        // dd($this->app->make(config('passport_booster.token_guard')));


        // dd($tokenGuard);
        return new RequestGuard(function ($request) use ($config) {
            $tokenGuard = config('passport_booster.token_guard');

            return (new $tokenGuard(
                    $this->app->make(ResourceServer::class),
                    Auth::createUserProvider($config['provider']),
                    $this->app->make(config('passport_booster.token_repository')),
                    $this->app->make(config('passport_booster.client_repository')),
                    $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }
}
