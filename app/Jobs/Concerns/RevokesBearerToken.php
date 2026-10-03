<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Api\Service;
use Throwable;

/**
 * The delete jobs run after the player has been signed out (the cookies are forgotten but the token is kept for
 * the job), so when the job has finished the token has to be revoked, otherwise it stays valid until it expires.
 *
 * @property Service $service
 */
trait RevokesBearerToken
{
    /**
     * Best effort, the job's real work is done by now and a revoke that fails must not stop the player being
     * told, the token expires in time even if we can't revoke it now
     */
    private function revokeBearerToken(): void
    {
        try {
            $response = $this->service->authLogout();

            if ($response['status'] !== 204 && $response['status'] !== 200) {
                report(new \RuntimeException(
                    'Unable to revoke the API token after deleting the account for user id ' . $this->user_id .
                    ', status ' . $response['status']
                ));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
