<?php
declare(strict_types=1);

namespace App\Actions\Account;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class DeleteAccount
{
    public function __invoke(
        string $bearer_token,
        string $user_id,
        string $email
    ): bool
    {
        \App\Jobs\DeleteAccount::dispatch(
            $bearer_token,
            $user_id,
            $email
        )->delay(now()->addSeconds(5));

        return true;
    }
}
