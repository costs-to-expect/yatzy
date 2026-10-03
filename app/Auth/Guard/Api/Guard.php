<?php

namespace App\Auth\Guard\Api;

use App\Api\Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Throwable;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Guard implements \Illuminate\Contracts\Auth\Guard
{
    private UserProvider $user_provider;
    private array $config;
    private Request $request;
    private ?Authenticatable $user;
    private array $errors = [];

    public function __construct(
        UserProvider $user_provider,
        array $config,
        Request $request
    )
    {
        $this->user_provider = $user_provider;
        $this->config = $config;
        $this->request = $request;
        $this->user = null;
    }

    public function attempt(array $credentials, bool $remember_me = false): bool
    {
        return $this->validate($credentials, $remember_me);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function check(): bool
    {
        return $this->request->cookie($this->config['cookie_bearer']) !== null &&
            $this->request->cookie($this->config['cookie_user']) !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->user instanceof Authenticatable) {
            return $this->user;
        }

        $user_id = $this->request->cookie($this->config['cookie_user']);
        if ($user_id === null) {
            return null;
        }

        $user = $this->user_provider->retrieveById($user_id);
        if ($user instanceof Authenticatable) {
            $this->setUser($user);

            return $this->user;
        }

        return null;
    }

    public function id()
    {
        if ($this->check() === false) {
            return null;
        }

        return $this->request->cookie($this->config['cookie_user']);
    }

    public function validate(array $credentials = [], bool $remember_me = false): bool
    {
        // The sign-in route is public, there is no bearer to send
        $api = new Service();

        if (array_key_exists('email', $credentials) === false || $credentials['email'] === null) {
            $this->errors['email']['errors'] = [
                'You need to provide your email address'
            ];
        }
        if (array_key_exists('password', $credentials) === false || $credentials['password'] === null) {
            $this->errors['password']['errors'] = [
                'You need to provide your password'
            ];
        }

        if (count($this->errors) > 0) {
            return false;
        }

        $response = $api->authSignIn(
            $credentials['email'],
            $credentials['password']
        );

        if ($response['status'] === 201) {

            $life_time = 43200;
            if ($remember_me === false) {
                $life_time = null;
            }

            Cookie::queue(
                $this->config['cookie_bearer'],
                $response['content']['token'],
                $life_time
            );
            Cookie::queue(
                $this->config['cookie_user'],
                $response['content']['id'],
                $life_time
            );

            return true;
        }

        if ($response['status'] === 422) {
            $this->errors = $response['fields'];
            return false;
        }

        if ($response['status'] === 401) {
            $this->errors = ['email' => ['errors' => [$response['content']]]];
            return false;
        }

        return false;
    }

    public function setUser(?Authenticatable $user): Guard
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Forget the player and, by default, revoke their bearer token in the API so it can't be used again
     *
     * Pass false when something still has to use the token after the player has gone, for example a
     * queued account deletion, the cookies are still forgotten.
     */
    public function logout(bool $revoke_api_token = true): void
    {
        $bearer = $this->request->cookie($this->config['cookie_bearer']);

        if ($revoke_api_token === true && $bearer !== null) {
            try {
                (new Service($bearer))->authLogout();
            } catch (Throwable $e) {
                // Never stop a player signing out, the token expires in time even if we can't revoke it now
                report($e);
            }
        }

        $config = Config::get('app.config');

        Cookie::queue(Cookie::forget($config['cookie_bearer']));
        Cookie::queue(Cookie::forget($config['cookie_user']));

        $this->user = null;

        Session::flush();
    }

    public function hasUser(): bool
    {
        return $this->user instanceof Authenticatable;
    }
}
