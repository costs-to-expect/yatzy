<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShareToken;
use App\Notifications\ApiError;
use App\Notifications\Bye;
use App\Notifications\ByeBye;
use App\Notifications\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportingClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_tokens_are_grouped_by_game_and_player(): void
    {
        foreach ([['t-1', 'g-1', 'p-1'], ['t-2', 'g-1', 'p-2'], ['t-3', 'g-2', 'p-1']] as [$token, $game, $player]) {
            $share = new ShareToken();
            $share->token = $token;
            $share->game_id = $game;
            $share->player_id = $player;
            $share->parameters = '{}';
            $share->save();
        }

        self::assertSame(
            ['g-1' => ['p-1' => 't-1', 'p-2' => 't-2'], 'g-2' => ['p-1' => 't-3']],
            (new ShareToken())->getShareTokens()
        );
    }

    public function test_no_share_tokens_is_an_empty_list(): void
    {
        self::assertSame([], (new ShareToken())->getShareTokens());
    }

    public function test_the_footer_shows_the_costs_to_expect_lockup_the_version_and_how_to_get_support(): void
    {
        $this->blade('<x-footer />')
            ->assertSee('A Costs to Expect app')
            ->assertSee('v'.config('app.version.app'))
            ->assertSee('support@costs-to-expect.com');
    }

    public function test_the_navigation_marks_the_current_page(): void
    {
        $html = (string) $this->blade('<x-layouts.app title="Games" active="games">content</x-layouts.app>');

        // The laptop navigation and the phone tab bar both mark Games, and only Games
        self::assertSame(2, preg_match_all('/<a href="[^"]*\/games"\s+aria-current="page"/', $html));
        self::assertSame(0, preg_match_all('/<a href="[^"]*\/home"\s+aria-current="page"/', $html));
        self::assertStringContainsString('data-tab-bar', $html);
        self::assertStringContainsString('content', $html);
    }

    public function test_the_signed_in_layout_has_the_account_and_a_way_to_sign_out(): void
    {
        $html = (string) $this->blade('<x-layouts.app title="Home" active="home">content</x-layouts.app>');

        self::assertStringContainsString(route('account'), $html);
        self::assertStringContainsString(route('sign-out'), $html);
        self::assertStringContainsString(route('players'), $html);
    }

    public function test_guests_see_the_brand_linking_to_the_landing_page_and_how_to_sign_in(): void
    {
        $html = (string) $this->blade('<x-layouts.guest title="Sign in">content</x-layouts.guest>');

        self::assertStringContainsString('<a href="'.route('landing').'"', $html);
        self::assertStringContainsString(route('sign-in.view'), $html);
        self::assertStringContainsString(route('register.view'), $html);
        self::assertStringNotContainsString('data-tab-bar', $html);
    }

    public function test_every_page_loads_the_compiled_css_for_the_current_version_and_the_shared_script(): void
    {
        $html = (string) $this->blade('<x-layouts.guest title="Sign in">content</x-layouts.guest>');

        self::assertStringContainsString('/css/'.config('app.version.css').'/app.css', $html);
        self::assertStringContainsString('/js/ui.js?v='.config('app.version.js'), $html);
        self::assertFileExists(public_path('css/'.config('app.version.css').'/app.css'));
        self::assertFileExists(public_path('js/ui.js'));
    }

    public function test_every_icon_the_sprite_offers_is_drawn_once(): void
    {
        $html = (string) $this->blade('<x-icon-sprite />');

        foreach ([...array_keys(\App\View\Icons::OUTLINE), ...array_keys(\App\View\Icons::SOLID)] as $name) {
            self::assertSame(1, substr_count($html, 'id="i-'.$name.'"'), $name);
        }
        foreach (array_keys(\App\View\Icons::MARKS) as $name) {
            self::assertSame(1, substr_count($html, 'id="m-'.$name.'"'), $name);
        }
    }

    public function test_an_icon_is_drawn_from_the_sprite_and_a_solid_icon_is_filled(): void
    {
        self::assertStringContainsString('<use href="#i-share"/>', (string) $this->blade('<x-icon name="share" />'));
        self::assertStringContainsString('stroke-width="1.75"', (string) $this->blade('<x-icon name="share" />'));
        self::assertStringContainsString('fill="currentColor"', (string) $this->blade('<x-icon name="crown" />'));
        self::assertStringNotContainsString('stroke-width', (string) $this->blade('<x-icon name="crown" />'));
        self::assertStringContainsString('<use href="#m-yatzy"/>', (string) $this->blade('<x-game-mark />'));
    }

    public function test_an_avatar_is_the_initial_on_the_colour_of_the_players_place_in_the_list(): void
    {
        $first = (string) $this->blade('<x-avatar name="łukasz" :index="0" />');
        $seventh = (string) $this->blade('<x-avatar name="Ada" :index="6" />');
        $second = (string) $this->blade('<x-avatar name="Ben" :index="1" />');

        self::assertStringContainsString('>Ł</span>', $first);
        self::assertStringContainsString('bg-rose-100', $first);
        self::assertStringContainsString('bg-rose-100', $seventh, 'the colours start again after the sixth');
        self::assertStringContainsString('bg-sky-100', $second);
    }

    public function test_the_ring_draws_the_fraction_of_the_turns_played_and_never_more_than_a_full_circle(): void
    {
        $circumference = 2 * M_PI * 33;

        self::assertStringContainsString(number_format($circumference / 2, 1, '.', '').' '.number_format($circumference, 1, '.', ''), (string) $this->blade('<x-ring :fraction="0.5" />'));
        self::assertStringContainsString('stroke-dasharray="'.number_format($circumference, 1, '.', ''), (string) $this->blade('<x-ring :fraction="3" />'));
        self::assertStringContainsString('stroke-dasharray="0.0 ', (string) $this->blade('<x-ring :fraction="-1" />'));
    }

    public function test_a_field_shows_its_label_help_and_the_apis_errors_and_points_at_them(): void
    {
        $html = (string) $this->blade(
            '<x-field name="email" type="email" label="Email" help="Where to write" :bag="$bag" :value="$value" required />',
            ['bag' => ['email' => ['errors' => ['It is not valid', 'It is taken']]], 'value' => 'ada@example.test']
        );

        self::assertStringContainsString('<label for="email" class="form-label">Email</label>', $html);
        self::assertStringContainsString('value="ada@example.test"', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('aria-describedby="email-help email-error"', $html);
        self::assertStringContainsString('form-control-error', $html);
        self::assertStringContainsString('It is not valid It is taken', $html);
        self::assertStringContainsString('Where to write', $html);
    }

    public function test_a_field_without_errors_is_not_marked_invalid(): void
    {
        $html = (string) $this->blade('<x-field name="name" label="Name" :bag="$bag" />', ['bag' => ['email' => ['errors' => ['No']]]]);

        self::assertStringNotContainsString('aria-invalid', $html);
        self::assertStringNotContainsString('form-control-error', $html);
        self::assertStringNotContainsString('aria-describedby', $html);
    }

    public function test_the_registered_email_invites_the_player_to_sign_in(): void
    {
        $mail = (new Registered())->toMail(new \stdClass());

        self::assertSame('Yatzy Game Scorer: Registered', $mail->subject);
        self::assertSame('Sign-in', $mail->actionText);
        self::assertSame(url('/sign-in'), $mail->actionUrl);
    }

    public function test_the_goodbye_emails_say_which_account_was_deleted(): void
    {
        $yatzy = (new Bye())->toMail(new \stdClass());
        $everything = (new ByeBye())->toMail(new \stdClass());

        self::assertSame('Yatzy Game Scorer: Bye', $yatzy->subject);
        self::assertContains('Your Yatzy account has been deleted.', $yatzy->introLines);

        self::assertSame('Yatzy Game Scorer: Bye', $everything->subject);
        self::assertContains('Your account has been deleted.', $everything->introLines);
    }

    public function test_the_error_email_carries_the_error_and_the_message(): void
    {
        $notification = new ApiError('Unable to log the score', 'The log is down');
        $mail = $notification->toMail(new \stdClass());

        self::assertSame('Yatzy Game Scorer: Error', $mail->subject);
        self::assertContains('Error: Unable to log the score', $mail->introLines);
        self::assertContains('Message: The log is down', $mail->introLines);
        self::assertSame(['mail'], $notification->via(new \stdClass()));
        self::assertSame(['error' => 'Unable to log the score', 'message' => 'The log is down'], $notification->toArray(new \stdClass()));
    }

    public function test_a_page_that_does_not_exist_is_a_plain_not_found_page(): void
    {
        $this->get('/not-a-page')
            ->assertNotFound()
            ->assertSee('Not Found')
            ->assertSee('404')
            ->assertSee('Back to the start');
    }
}
