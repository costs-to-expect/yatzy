<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class PlayersTest extends TestCase
{
    use FakesTheApi;

    private const PLAYERS = 'api.test/v3/resource-types/rt-1/categories';

    public function test_the_players_page_lists_every_player(): void
    {
        $this->signedIn()->get('/players')
            ->assertOk()
            ->assertSee('Players')
            ->assertSee('Ada')
            ->assertSee('Ben')
            ->assertSee(route('player.create.view'), false);
    }

    public function test_the_players_page_explains_when_there_are_no_players(): void
    {
        $this->fakeApi(['api.test/v3/resource-types/rt-1/categories?collection=1' => Http::response([], 200)]);

        $this->signedIn()->get('/players')
            ->assertOk()
            ->assertDontSee('Ada');
    }

    public function test_the_new_player_form_posts_a_name(): void
    {
        $this->signedIn()->get('/new-player')
            ->assertOk()
            ->assertSee('action="'.route('player.create.action').'"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false);
    }

    public function test_a_player_is_created_with_their_name_and_description(): void
    {
        $this->fakeApi([self::PLAYERS => Http::response(['id' => 'p-3'], 201)]);

        $this->signedIn()
            ->post('/new-player', ['name' => 'Cleo', 'description' => 'New player - Added via the Yatzy App'])
            ->assertRedirect(route('players'));

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'http://api.test/v3/resource-types/rt-1/categories'
            && $request->data() === ['name' => 'Cleo', 'description' => 'New player - Added via the Yatzy App']
            && $request->header('Authorization') === ['Bearer test-bearer-token']);
    }

    public function test_a_name_the_api_rejects_returns_to_the_form_with_the_reason(): void
    {
        $this->fakeApi([self::PLAYERS => Http::response([
            'message' => 'Validation error.',
            'fields' => ['name' => ['errors' => ['The name has already been taken.']]],
        ], 422)]);

        $this->signedIn()
            ->post('/new-player', ['name' => 'Ada', 'description' => 'Again'])
            ->assertRedirect(route('player.create.view'))
            ->assertSessionHas('validation.errors', ['name' => ['errors' => ['The name has already been taken.']]]);

        $this->signedIn()
            ->followingRedirects()
            ->post('/new-player', ['name' => 'Ada', 'description' => 'Again'])
            ->assertSee('The name has already been taken.')
            ->assertSee('value="Ada"', false);
    }

    public function test_any_other_api_failure_is_passed_on(): void
    {
        $this->fakeApi([self::PLAYERS => Http::response(['message' => 'The API is down'], 503)]);

        $this->signedIn()
            ->post('/new-player', ['name' => 'Cleo', 'description' => 'New player'])
            ->assertStatus(503);
    }
}
