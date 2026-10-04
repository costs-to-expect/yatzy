<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;
use App\Notifications\ApiError;
use App\Support\ScoreRules;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

/**
 * Scores a combination on a player's score sheet, or, when corrections are switched on, changes or clears one that is
 * already scored. The signed-in player and the public share link both come through here, so a score is checked in one
 * place: it has to be a combination that exists, a score the combination can produce and an empty slot.
 *
 * __invoke returns the HTTP status for the browser: 200 done, 403 corrections are off, 409 the combination is already
 * scored, 422 the score is not allowed, anything else is the API's status.
 */
class ChangeScore extends Action
{
    private array $sheet = [];

    private bool $failed_to_save = false;

    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id,
        string $section,
        mixed $combination,
        mixed $score,
        bool $replace = false,
        bool $clear = false
    ): int
    {
        $sheet_response = $api->getPlayerScoreSheet($resource_type_id, $resource_id, $game_id, $player_id);

        if ($sheet_response['status'] !== 200) {
            $this->message = 'Unable to fetch your score sheet';
            $this->failed_to_save = true;

            return $sheet_response['status'];
        }

        $this->sheet = $sheet_response['content']['value'];
        $corrections = (bool) Config::get('app.config.score_corrections', false);

        if (is_string($combination) === false || ScoreRules::isCombination($section, $combination) === false) {
            $this->message = 'That is not a combination on the score sheet';

            return 422;
        }

        $previous = ScoreRules::value($this->sheet, $section, $combination);

        if ($clear === true) {
            if ($corrections === false) {
                $this->message = 'Scores cannot be cleared';

                return 403;
            }

            $problem = ScoreRules::clearProblem($this->sheet, $section, $combination);
            if ($problem !== null) {
                $this->message = $problem;

                return 422;
            }

            $updated = ScoreRules::without($this->sheet, $section, $combination);
            $message = 'Cleared their ' . ScoreRules::label($combination);
            $parameters = ['player' => $player_id, 'section' => $section, 'action' => 'clear', 'previous' => $previous];
        } else {
            $problem = ScoreRules::problem($this->sheet, $section, $combination, $score);
            if ($problem !== null) {
                $this->message = $problem;

                return 422;
            }

            $points = ScoreRules::integer($score);

            if ($previous === $points) {
                // The same score again, a retry of a save that reached us but whose answer got lost
                $this->message = 'Score updated';

                return 200;
            }

            if ($previous !== null) {
                if ($replace === false) {
                    $this->message = 'That combination has already been scored';

                    return 409;
                }

                if ($corrections === false) {
                    $this->message = 'Scores cannot be changed';

                    return 403;
                }
            }

            $updated = ScoreRules::with($this->sheet, $section, $combination, $points);
            $message = $previous === null
                ? $this->scoredMessage($section, $combination, $points)
                : 'Changed their ' . ScoreRules::label($combination) . ' from ' . $previous . ' to ' . $points;
            $parameters = [
                'player' => $player_id,
                'section' => $section,
                $section === ScoreRules::UPPER_SECTION ? 'dice' : 'combo' => $combination,
                'score' => $points,
            ];
            if ($previous !== null) {
                $parameters['previous'] = $previous;
            }
        }

        $result = (new Score())($api, $resource_type_id, $resource_id, $game_id, $player_id, $updated);

        if ($result !== 204) {
            $this->message = 'Failed to update your score sheet';
            $this->failed_to_save = true;

            return $result;
        }

        $this->sheet = $updated;
        $this->message = $clear === true ? 'Score cleared' : 'Score updated';

        $log = new Log();
        if ($log($api, $resource_type_id, $resource_id, $game_id, $message, $parameters) !== 201) {
            Notification::route('mail', Config::get('app.config')['error_email'])
                ->notify(new ApiError(
                    'Unable to log the score for the ' . $section . ' section',
                    $log->getMessage()
                ));
        }

        return 200;
    }

    /**
     * The sheet as it is now, for a status the browser can use to catch up (a score that was already there)
     */
    public function getSheet(): array
    {
        return $this->sheet;
    }

    /**
     * True when the failure was reading or saving the sheet, the browser is only told that, never the sheet
     */
    public function failedToSave(): bool
    {
        return $this->failed_to_save;
    }

    private function scoredMessage(string $section, string $combination, int $points): string
    {
        if ($section === ScoreRules::UPPER_SECTION) {
            return 'Scored ' . $points . ' in their ' . ucfirst($combination);
        }

        if (in_array($combination, ScoreRules::SUMS, true)) {
            return 'Scored ' . $points . ' in ' . ScoreRules::label($combination);
        }

        return 'Scored their ' . ScoreRules::label($combination) . ', scoring ' . $points;
    }
}
