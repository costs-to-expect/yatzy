<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 *
 * @property string $token
 * @property string $game_id
 * @property string $player_id
 * @property string $parameters
 */
class ShareToken extends Model
{
    protected $table = 'share_token';

    public function getShareTokens(): array
    {
        $tokens = [];

        foreach (self::query()->get() as $token) {
            $tokens[$token->game_id][$token->player_id] = $token->token;
        }

        return $tokens;
    }
}
