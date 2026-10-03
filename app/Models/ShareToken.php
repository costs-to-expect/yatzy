<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EncryptedParameters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 *
 * @property string $token
 * @property string $game_id
 * @property string $player_id
 * @property array<string, mixed> $parameters The resource ids, the player's name and the owner's bearer token, encrypted at rest
 */
class ShareToken extends Model
{
    protected $table = 'share_token';

    protected $primaryKey = 'token';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = ['parameters'];

    protected function casts(): array
    {
        return [
            'parameters' => EncryptedParameters::class,
        ];
    }

    /**
     * Creates the public link for a player in a game, the link lets anyone who holds it score for that
     * player, using the owner's bearer token, until the game is completed or deleted.
     */
    public static function issue(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id,
        string $player_name,
        ?string $owner_bearer
    ): self
    {
        $share = new self();
        $share->token = (string) Str::uuid();
        $share->game_id = $game_id;
        $share->player_id = $player_id;
        $share->parameters = [
            'resource_type_id' => $resource_type_id,
            'resource_id' => $resource_id,
            'game_id' => $game_id,
            'player_id' => $player_id,
            'player_name' => $player_name,
            'owner_bearer' => $owner_bearer,
        ];
        $share->save();

        return $share;
    }

    /**
     * The parameters behind a public link, a 404 for a link that does not exist and a 500 for a row that can
     * no longer be read (the application key changed since it was written)
     *
     * @return array{resource_type_id: string, resource_id: string, game_id: string, player_id: string, player_name: string, owner_bearer: string}
     */
    public static function parametersFor(string $token): array
    {
        $share = self::query()->where('token', $token)->first();
        if ($share === null) {
            abort(404, 'The game page for the token does not exist');
        }

        try {
            $parameters = $share->parameters;
        } catch (\Throwable) {
            abort(500, 'Failed to decode the parameters for the token');
        }

        if (array_key_exists('player_name', $parameters) === false) {
            $parameters['player_name'] = 'Yatzy Player';
        }

        return $parameters;
    }

    /**
     * The share tokens, grouped by game and then player
     *
     * @param list<string>|null $game_ids Only the tokens of these games, null for every token
     * @return array<string, array<string, string>>
     */
    public function getShareTokens(?array $game_ids = null): array
    {
        $query = self::query();
        if ($game_ids !== null) {
            $query->whereIn('game_id', $game_ids);
        }

        $tokens = [];

        foreach ($query->get(['token', 'game_id', 'player_id']) as $token) {
            $tokens[$token->game_id][$token->player_id] = $token->token;
        }

        return $tokens;
    }
}
