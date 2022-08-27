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

