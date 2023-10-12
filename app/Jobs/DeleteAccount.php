<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Api\Service;
use App\Notifications\ApiError;
use App\Notifications\ByeBye;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class DeleteAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;

    private string $bearer_token;
    private string $user_id;
    private string $email;

    private Service $service;

    public function __construct(
        string $bearer_token,
        string $user_id,
        string $email
    )
    {
        $this->bearer_token = $bearer_token;
        $this->user_id = $user_id;
        $this->email = $email;
    }

    public function handle()
    {
        $this->apiService();

        $response = $this->service->requestAccountDelete();

        if ($response['status'] !== 201) {
            $this->fail(
                (new \Exception(
                    'Unable to request the deletion of account with user id ' . $this->user_id . ' error: ' .
                    json_encode($response['content'])
                ))
            );
        }

        DB::table('sessions')
            ->where('user_id', '=', $this->user_id)
            ->delete();

        Notification::route('mail', $this->email)
            ->notify(new ByeBye());
    }

    public function failed(Throwable $exception)
    {
        $config = Config::get('app.config');

        Notification::route('mail', $config['error_email'])
            ->notify(new ApiError(
                'Unable to delete the account for user id ' . $this->user_id,
                $exception->getMessage()
            ));
    }

    private function apiService()
    {
        $this->service = new Service($this->bearer_token);
    }
}