<?php
declare(strict_types=1);

namespace App\Actions;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
abstract class Action
{
    protected string $message;

    protected array $validation_errors = [];

    protected string $game_id;

    public function getValidationErrors(): array
    {
        return $this->validation_errors;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getGameId(): string
    {
        return $this->game_id;
    }
}
