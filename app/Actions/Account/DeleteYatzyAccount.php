<?php
declare(strict_types=1);

namespace App\Actions\Account;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class DeleteYatzyAccount
{
    public function __invoke(
        string $bearer_token,
        string $resource_type_id,
        string $resource_id,
        string $user_id,
        string $email
    ): bool
    {
        \App\Jobs\DeleteYatzyAccount::dispatch(
            $bearer_token,
            $resource_type_id,
            $resource_id,
            $user_id,
            $email
        )->delay(now()->addSeconds(5));

        return true;
    }
}
