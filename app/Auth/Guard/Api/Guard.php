<?php

namespace App\Auth\Guard\Api;

use App\Api\Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

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

    public function attempt(array $credentials, bool $remember_me): bool
    {
        return $this->validate($credentials, $remember_me);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function check(): bool
    {
        return $this->request->hasCookie($this->config['cookie_bearer']) === true &&
            $this->request->hasCookie($this->config['cookie_user']) === true &&
            $this->request->cookie($this->config['cookie_user']) !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        return $this->user ??
            $this->user_provider->retrieveById($this->request->cookie($this->config['cookie_user']));
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
        $api = new Service($this->request->cookie($this->config['cookie_bearer']));

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
            $this->errors = $response['content']['fields'];
            return false;
        }

        if ($response['status'] === 401) {
            $this->errors = ['email' => [$response['content']['message']]];
            return false;
        }

        return false;
    }

    public function setUser(?Authenticatable $user): Guard
    {
        $this->user = $user;

        return $this;
    }

    public function logout(): void
    {
        $config = Config::get('app.config');

        Cookie::queue(Cookie::forget($config['cookie_bearer']));
        Cookie::queue(Cookie::forget($config['cookie_user']));

        Session::flush();
    }

    public function hasUser(): bool
    {
        return $this->user instanceof Authenticatable;
    }
}
