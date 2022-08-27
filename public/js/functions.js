export function display_selected_toast(show_toast) {
    if (show_toast !== 'none') {
        const toast = new window.bootstrap.Toast(document.getElementById('toast_' + show_toast))
        toast.show();

        if (show_toast === 'yahtzee') {
            window.confetti({
                particleCount: 100,
            })
        }
        if (show_toast.startsWith('yahtzee_bonus')) {
            window.confetti({
                particleCount: 500,
            })
        }
    }
}

export function disable_yahtzee_bonus_if_game_over(turns) {
    if (turns === 13) {
        let bonus_one = document.querySelector('input[type="checkbox"]#yahtzee_bonus_one.active');
        if (bonus_one !== null) {
            bonus_one.disabled = true;
        }
        let bonus_two = document.querySelector('input[type="checkbox"]#yahtzee_bonus_two.active');
        if (bonus_two !== null) {
            bonus_two.disabled = true;
        }
        let bonus_three = document.querySelector('input[type="checkbox"]#yahtzee_bonus_three.active');
        if (bonus_three !== null) {
            bonus_three.disabled = true;
        }

        display_selected_toast('done');
    }
}

export function disable_yahtzee_bonus() {
    let bonus_one = document.querySelector('input[type="checkbox"]#yahtzee_bonus_one.active');
    if (bonus_one !== null) {
        bonus_one.disabled = true;
    }
    let bonus_two = document.querySelector('input[type="checkbox"]#yahtzee_bonus_two.active');
    if (bonus_two !== null) {
        bonus_two.disabled = true;
    }
    let bonus_three = document.querySelector('input[type="checkbox"]#yahtzee_bonus_three.active');
    if (bonus_three !== null) {
        bonus_three.disabled = true;
    }
}
