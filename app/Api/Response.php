<?php
declare(strict_types=1);

namespace App\Api;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yatzy/blob/main/LICENSE
 */
class Response
{
    private array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function body(): array
    {
        return $this->response['response']['body'];
    }

    public function headers(): array
    {
        return $this->response['response']['headers'];
    }

    public function hasResponse(): bool
    {
        return array_key_exists('response', $this->response) === true;
    }
}
