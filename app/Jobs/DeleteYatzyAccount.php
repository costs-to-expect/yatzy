<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Game\Delete;
use App\Api\Service;
use App\Notifications\ApiError;
use App\Notifications\Bye;
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
class DeleteYatzyAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;

    private string $bearer_token;
    private string $resource_type_id;
    private string $resource_id;
    private string $user_id;
    private string $email;

    private Service $service;

    public function __construct(
        string $bearer_token,
        string $resource_type_id,
        string $resource_id,
        string $user_id,
        string $email
    )
    {
        $this->bearer_token = $bearer_token;
        $this->resource_type_id = $resource_type_id;
        $this->resource_id = $resource_id;
        $this->user_id = $user_id;
        $this->email = $email;
    }

    public function handle()
    {
        $this->apiService();

        while($this->getGames() !== null) {

            foreach ($this->getGames() as $game) {
                $action = new Delete();
                try {
                    $result = $action(
                        $this->service,
                        $this->resource_type_id,
                        $this->resource_id,
                        $game['id']
                    );

                    if ($result !== 204) {
                        $this->fail((new \Exception('Unable to delete game with id ' . $game['id'])));
                    }
                } catch (\Exception $e) {
                    $this->fail($e);
                }
            }
        }

        $config = Config::get('app.config');
        $resources = $this->service->getResources($this->resource_type_id, ['item-subtype' => $config['item_subtype_id']]);

        if (
            $resources['status'] === 200 &&
            count($resources['content']) === 1
        ) {
            $response = $this->service->deleteResource(
                $this->resource_type_id,
                $this->resource_id
            );

            if ($response['status'] !== 204 && $response['status'] !== 404) {
                $this->fail(
                    (new \Exception(
                        'Unable to delete the Yatzy resource with id  ' . $this->resource_id . ' error: ' .
                        json_encode($response['content'])
                    ))
                );
            }
        }

        DB::table('sessions')
            ->where('user_id', '=', $this->user_id)
            ->delete();

        Notification::route('mail', $this->email)
            ->notify(new Bye());
    }

    public function failed(Throwable $exception)
    {
        $config = Config::get('app.config');

        Notification::route('mail', $config['error_email'])
            ->notify(new ApiError(
                'Unable to delete the Yatzy account for user id ' . $this->user_id,
                $exception->getMessage()
            ));
    }

    private function apiService()
    {
        $this->service = new Service($this->bearer_token);
    }

    private function getGames()
    {
        $result = $this->service->getGames(
            $this->resource_type_id,
            $this->resource_id,
            [
                'limit' => 25
            ],
            true
        );

        if ($result['status'] === 200 && count($result['content']) > 0) {
            return $result['content'];
        }

        if ($result['status'] !== 200) {
            $this->fail((new \Exception($result['content'])));
        }

        return null;
    }
}
