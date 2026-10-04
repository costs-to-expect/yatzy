<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use FakesTheApi;

    public function test_guests_see_the_landing_page_with_a_way_to_sign_in_or_register(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Yatzy Game Scorer')
            ->assertSee(route('sign-in.view'), false)
            ->assertSee(route('register.view'), false);
    }

    public function test_the_landing_page_is_built_to_be_found_and_shared(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertDontSee('noindex');
    }

    public function test_the_landing_page_describes_yatzy_to_search_engines_and_social_cards(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Yatzy Game Scorer: Online Yatzy Score Sheets</title>', false)
            ->assertSee('<meta name="description" content="Score your Yatzy games online.', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/card.png').'">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('images/card.png').'">', false);
    }

    public function test_the_landing_page_has_a_score_sheet_to_try_and_a_walkthrough(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="demo"', false)
            ->assertSee('Try the score sheet')
            ->assertSee('id="walk"', false)
            ->assertSee('How a game night goes')
            ->assertSee('js/landing.js', false);
    }

    public function test_every_step_of_the_walkthrough_has_a_picture_that_exists(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('#images/([a-z-]+\.png)#', $html, $matches);
        $pictures = array_unique($matches[1]);

        foreach (['new-game.png', 'management.png', 'score-sheet.png', 'player-scores.png'] as $picture) {
            self::assertContains($picture, $pictures);
        }

        foreach ($pictures as $picture) {
            self::assertFileExists(public_path('images/'.$picture));
        }
    }

    public function test_the_landing_page_footer_shows_the_app_version(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('v'.config('app.version.app'));
    }

    public function test_the_authentication_pages_are_not_indexed(): void
    {
        foreach (['/sign-in', '/register'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex,follow">', false);
        }
    }
}
