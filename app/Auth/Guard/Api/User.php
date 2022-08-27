<?php

declare(strict_types=1);

namespace App\Auth\Guard\Api;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Config;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class User extends Authenticatable
{
    protected $keyType = 'string';

    public function getAuthIdentifier()
    {
        $config = Config::get('app.config');
        return request()?->cookie($config['cookie_user']);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
}
