<?php

namespace App\Auth\Guard\Api;

use App\Api\Service;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    private array $config;
    private Request $request;

    public function __construct(array $config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * There is no local user, the user is whoever the API says owns the bearer token
     * in the player's cookie
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        $bearer = $this->request->cookie($this->config['cookie_bearer']);
        if ($bearer === null) {
            return null;
        }

        $user_response = (new Service($bearer))->getAuthUser();
        if ($user_response['status'] === 200) {
            $user = new User();
            $user->id = $user_response['content']['id'];
            $user->name = $user_response['content']['name'];
            $user->email = $user_response['content']['email'];

            return $user;
        }

        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return $this->retrieveById($identifier);
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // Not necessary
    }

    public function retrieveByCredentials(array $credentials): void
    {
        // Not necessary
    }

    public function validateCredentials(Authenticatable $user, array $credentials): void
    {
        // Not necessary
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Not necessary, the API owns the passwords
    }
}
