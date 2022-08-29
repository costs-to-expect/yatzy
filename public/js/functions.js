export function display_selected_toast(show_toast) {
    if (show_toast !== 'none') {
        const toast = new window.bootstrap.Toast(document.getElementById('toast_' + show_toast))
        toast.show();

        if (show_toast === 'yatzy') {
            window.confetti({
                particleCount: 100,
            })
        }
    }
}

export function game_over(turns) {
    if (turns === 15) {
        display_selected_toast('done');
    }
}