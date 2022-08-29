import {display_selected_toast, game_over} from './functions.js';

(function (axios) {
    'use strict'

    let game_id = document.getElementById('game_id');
    let player_id = document.getElementById('player_id');
    let token = document.getElementById('token');

    let uri_score_upper = (token == null) ? '/game/score-upper' : '/public/score-sheet/' + token.value + '/score-upper';
    let uri_score_lower = (token == null) ? '/game/score-lower' : '/public/score-sheet/' + token.value + '/score-lower';

    let timeout = null;
    let delay = 1000;

    let factors = { "ones": 1, "twos": 2, "threes": 3, "fours": 4, "fives": 5, "sixes": 6 }

    let player_score_upper = document.getElementById('upper-score');
    let player_score_bonus = document.getElementById('upper-bonus');
    let player_score_upper_total = document.getElementById('upper-total');
    let player_score_lower_upper = document.getElementById('lower-upper-total');
    let player_score_lower = document.getElementById('lower-score');
    let player_total_score = document.getElementById('total');
    let player_final_score = document.getElementById('final-score');

    document.querySelectorAll('div.upper-section-scratch input[type="checkbox"].active').forEach(upper_scratch => {
       upper_scratch.addEventListener('change', function () {
           scratch_upper_combination(this);
       });
    });

    document.querySelectorAll('div.upper-section input[type="number"].active').forEach(upper =>
        upper.addEventListener('change', function() {
            score_upper_combination(this)
        })
    );

    let one_pair = document.querySelector('input[type="number"]#one_pair.active');
    if (one_pair !== null) {
        one_pair.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_one_pair = document.querySelector('input[type="checkbox"]#scratch_one_pair.active');
    if (scratch_one_pair !== null) {
        scratch_one_pair.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let two_pair = document.querySelector('input[type="number"]#two_pair.active');
    if (two_pair !== null) {
        two_pair.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_two_pair = document.querySelector('input[type="checkbox"]#scratch_two_pair.active');
    if (scratch_two_pair !== null) {
        scratch_two_pair.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let three_of_a_kind = document.querySelector('input[type="number"]#three_of_a_kind.active');
    if (three_of_a_kind !== null) {
        three_of_a_kind.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_three_of_a_kind = document.querySelector('input[type="checkbox"]#scratch_three_of_a_kind.active');
    if (scratch_three_of_a_kind !== null) {
        scratch_three_of_a_kind.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let four_of_a_kind = document.querySelector('input[type="number"]#four_of_a_kind.active');
    if (four_of_a_kind !== null) {
        four_of_a_kind.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_four_of_a_kind = document.querySelector('input[type="checkbox"]#scratch_four_of_a_kind.active');
    if (scratch_four_of_a_kind !== null) {
        scratch_four_of_a_kind.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let full_house = document.querySelector('input[type="number"]#full_house.active');
    if (full_house !== null) {
        full_house.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let scratch_full_house = document.querySelector('input[type="checkbox"]#scratch_full_house.active');
    if (scratch_full_house !== null) {
        scratch_full_house.addEventListener('change', function () {
            scratch_lower_combination(this);
        });
    }

    let small_straight = document.querySelector('input[type="checkbox"]#small_straight.active');
    if (small_straight !== null) {
        small_straight.addEventListener('change', function () {
            score_lower_fixed_combination(this, 15);
        });
    }

    let scratch_small_straight = document.querySelector('input[type="checkbox"]#scratch_small_straight.active');
    if (scratch_small_straight !== null) {
        scratch_small_straight.addEventListener('change', function () {
            scratch_lower_fixed_combination(this);
        });
    }

    let large_straight = document.querySelector('input[type="checkbox"]#large_straight.active');
    if (large_straight !== null) {
        large_straight.addEventListener('change', function () {
            score_lower_fixed_combination(this, 30);
        });
    }

    let scratch_large_straight = document.querySelector('input[type="checkbox"]#scratch_large_straight.active');
    if (scratch_large_straight !== null) {
        scratch_large_straight.addEventListener('change', function () {
            scratch_lower_fixed_combination(this);
        });
    }

    let yatzy = document.querySelector('input[type="checkbox"]#yatzy.active');
    if (yatzy !== null) {
        yatzy.addEventListener('change', function () {
            score_lower_fixed_combination(this, 50, 'yatzy');
        });
    }

    let scratch_yatzy = document.querySelector('input[type="checkbox"]#scratch_yatzy.active');
    if (scratch_yatzy !== null) {
        scratch_yatzy.addEventListener('change', function () {
            scratch_lower_combination(this, 'yatzy_scratch');
        });
    }

    let chance = document.querySelector('input[type="number"]#chance.active');
    if (chance !== null) {
        chance.addEventListener('change', function () {
            score_lower_combination(this);
        });
    }

    let score_lower_combination = function(element, show_toast = 'none') {

        let score = parseInt(element.value);

        // Work out max and min for combination
        // Limit to the relevant max and min

        if (score >= 2 && score <= 30) {

            clearTimeout(timeout);

            timeout = setTimeout(() => {

                let payload = {
                    combo: element.id,
                    score: score
                }

                if (token === null) {
                    payload.game_id = game_id.value;
                    payload.player_id = player_id.value;
                } else {
                    payload.token = token;
                }

                axios.post(
                    uri_score_lower,
                    payload
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    let scratch = document.getElementById('scratch_' + element.id);
                    if (scratch !== null) {
                        scratch.classList.remove('active');
                        scratch.classList.add('disabled');
                        scratch.disabled = true;
                    }

                    player_score_lower.innerText = response.data.score.lower;
                    player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    display_selected_toast(show_toast);

                    game_over(response.data.turns);

                    document.querySelectorAll('p.' + element.id + '_dice svg').forEach(dice =>
                        dice.classList.add('scored')
                    );
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

    let score_lower_fixed_combination = function(element, score, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {

            let payload = {
                combo: element.id,
                score: score
            }

            if (token === null) {
                payload.game_id = game_id.value;
                payload.player_id = player_id.value;
            } else {
                payload.token = token;
            }

            axios.post(
                uri_score_lower,
                payload
            )
            .then(response => {
                element.classList.remove('active');
                element.classList.add('disabled');
                element.disabled = true;

                let scratch = document.getElementById('scratch_' + element.id);
                scratch.classList.remove('active');
                scratch.classList.add('disabled');
                scratch.disabled = true;

                player_score_lower.innerText = response.data.score.lower;
                player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                display_selected_toast(show_toast);

                game_over(response.data.turns);

                document.querySelectorAll('p.' + element.id + '_dice svg').forEach(dice =>
                    dice.classList.add('scored')
                );
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let scratch_lower_combination = function(element, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {

            let payload = {
                combo: element.id.toString().replace('scratch_', ''),
                score: 0
            }

            if (token === null) {
                payload.game_id = game_id.value;
                payload.player_id = player_id.value;
            } else {
                payload.token = token;
            }

            axios.post(
                uri_score_lower,
                payload
            )
            .then(response => {
                element.classList.remove('active');
                element.classList.add('disabled');
                element.disabled = true;

                let lower = document.getElementById(element.id.toString().replace('scratch_', ''));
                lower.classList.remove('active');
                lower.classList.add('disabled');
                lower.disabled = true;
                lower.value = 0;

                player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                display_selected_toast(show_toast);

                game_over(response.data.turns);

                document.querySelectorAll('p.' + element.id.toString().replace('scratch_', '') + '_dice svg').forEach(dice =>
                    dice.classList.add('scored')
                );
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let scratch_lower_fixed_combination = function(element, show_toast = 'none') {
        clearTimeout(timeout);

        timeout = setTimeout(() => {

            let payload = {
                combo: element.id.toString().replace('scratch_', ''),
                score: 0
            }

            if (token === null) {
                payload.game_id = game_id.value;
                payload.player_id = player_id.value;
            } else {
                payload.token = token;
            }

            axios.post(
                uri_score_lower,
                payload
            )
            .then(response => {
                element.classList.remove('active');
                element.classList.add('disabled');
                element.disabled = true;

                let lower = document.getElementById(element.id.toString().replace('scratch_', ''));
                lower.classList.remove('active');
                lower.classList.add('disabled');
                lower.disabled = true;

                player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                display_selected_toast(show_toast);

                game_over(response.data.turns);

                document.querySelectorAll('p.' + element.id.toString().replace('scratch_', '') + '_dice svg').forEach(dice =>
                    dice.classList.add('scored')
                );
            })
            .catch(error => {
                console.log(error);
            });
        }, delay);
    }

    let score_upper_combination = function(element, show_toast = 'none') {

        let score = parseInt(element.value);
        let scoring_dice = 0;
        if (factors.hasOwnProperty(element.name)) {
            scoring_dice = (score / factors[element.name]);
        } else {
            score = 0; // Set score to zero if name not valid
        }

        if (
            score > 0 &&
            scoring_dice % 1 === 0 &&
            scoring_dice >= 1 &&
            scoring_dice <= 6
        ) {
            clearTimeout(timeout);
            timeout = setTimeout(() => {

                let payload = {
                    dice: element.id,
                    score: score
                }

                if (token === null) {
                    payload.game_id = game_id.value;
                    payload.player_id = player_id.value;
                } else {
                    payload.token = token;
                }

                axios.post(
                    uri_score_upper,
                    payload
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    let scratch = document.getElementById('scratch_' + element.id);
                    scratch.classList.remove('active');
                    scratch.classList.add('disabled');
                    scratch.disabled = true;

                    player_score_upper.innerText= response.data.score.upper;
                    player_score_bonus.innerText = response.data.score.bonus;
                    player_score_upper_total.innerText = response.data.score.upper + response.data.score.bonus;
                    player_score_lower_upper.innerText = response.data.score.upper + response.data.score.bonus;
                    player_total_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;
                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    display_selected_toast(show_toast);

                    game_over(response.data.turns);

                    document.querySelectorAll('label[for="' + element.id + '"] svg').forEach(dice =>
                        dice.classList.add('scored')
                    );

                    let bonus_message = document.querySelector('div.bonus-message');
                    let bonus_message_uri = (token === null) ? '/game/' + game_id.value + '/player/' + player_id.value + '/bonus' : '/public/game/' + token.value + '/bonus';

                    axios.get(bonus_message_uri)
                        .then(response => {
                            if (response.data.length > 0) {
                                bonus_message.innerHTML = response.data;
                            }
                        });
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

    let scratch_upper_combination = function(element, show_toast = 'none') {
        if (factors.hasOwnProperty(element.value) && element.checked === true) {

            clearTimeout(timeout);

            timeout = setTimeout(() => {

                let payload = {
                    dice: element.value,
                    score: 0
                }

                if (token === null) {
                    payload.game_id = game_id.value;
                    payload.player_id = player_id.value;
                } else {
                    payload.token = token;
                }

                axios.post(
                    uri_score_upper,
                    payload
                )
                .then(response => {
                    element.classList.remove('active');
                    element.classList.add('disabled');
                    element.disabled = true;

                    let upper = document.getElementById(element.value);
                    upper.classList.remove('active');
                    upper.classList.add('disabled');
                    upper.value = 0;
                    upper.disabled = true;

                    player_final_score.innerText = response.data.score.upper + response.data.score.bonus + response.data.score.lower;

                    display_selected_toast(show_toast);

                    game_over(response.data.turns);

                    document.querySelectorAll('label[for="' + element.value + '"] svg').forEach(dice =>
                        dice.classList.add('scored')
                    );

                    let bonus_message = document.querySelector('div.bonus-message');
                    let bonus_message_uri = (token === null) ? '/game/' + game_id.value + '/player/' + player_id.value + '/bonus' : '/public/game/' + token.value + '/bonus';

                    axios.get(bonus_message_uri)
                        .then(response => {
                            if (response.data.length > 0) {
                                bonus_message.innerHTML = response.data;
                            }
                        });
                })
                .catch(error => {
                    console.log(error);
                });
            }, delay);
        }
    }

})(axios, bootstrap, confetti);