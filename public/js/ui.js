/*
 * The pieces every game scorer page shares: icons, the sheet (a native <dialog>), the snackbar, the confetti, copying
 * a link, the options menu and the player picker. Plain JavaScript, no build step and no dependencies.
 *
 * The pages are server rendered, this only adds behaviour. Anything a page needs from here is on window.UI.
 */
(function () {
    'use strict';

    // ---- Small helpers -----------------------------------------------------------------------------------------------

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    function prefersReducedMotion() {
        return Boolean(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    // ---- Icons: drawn from the sprite the layout includes (resources/views/components/icon-sprite.blade.php) ------------

    var SOLID_ICONS = ['crown', 'star', 'check-circle'];

    function icon(name, classes) {
        var solid = SOLID_ICONS.indexOf(name) !== -1;
        var attributes = solid
            ? 'fill="currentColor"'
            : 'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';

        return '<svg class="' + (classes || 'h-5 w-5') + '" viewBox="0 0 24 24" ' + attributes + ' aria-hidden="true"><use href="#i-' + name + '"/></svg>';
    }

    // ---- Players: a player keeps their colour, it comes from their place in the players list ----------------------------

    var TONES = [
        'bg-rose-100 text-rose-800', 'bg-sky-100 text-sky-800', 'bg-lime-100 text-lime-800',
        'bg-violet-100 text-violet-800', 'bg-orange-100 text-orange-800', 'bg-slate-200 text-slate-700'
    ];

    function avatar(name, index, classes) {
        return '<span class="inline-flex shrink-0 items-center justify-center rounded-full font-bold ' + TONES[Math.abs(index) % TONES.length] + ' ' + (classes || 'h-9 w-9 text-sm') + '" aria-hidden="true">' +
            escapeHtml(String(name).trim().charAt(0).toUpperCase()) + '</span>';
    }

    // A progress ring that wraps an avatar, fraction is 0 to 1
    function ring(fraction, classes) {
        var radius = 33, circumference = 2 * Math.PI * radius;

        return '<svg class="' + (classes || 'h-14 w-14') + '" viewBox="0 0 76 76" fill="none" aria-hidden="true">' +
            '<circle cx="38" cy="38" r="' + radius + '" class="stroke-stone-200" stroke-width="5"/>' +
            '<circle cx="38" cy="38" r="' + radius + '" class="stroke-brand-600" stroke-width="5" stroke-linecap="round" stroke-dasharray="' +
            (circumference * Math.min(1, Math.max(0, fraction))).toFixed(1) + ' ' + circumference.toFixed(1) + '" transform="rotate(-90 38 38)"/></svg>';
    }

    // ---- A die, for the upper section rows -------------------------------------------------------------------------------

    var PIPS = {
        1: [[20, 20]], 2: [[11, 11], [29, 29]], 3: [[11, 11], [20, 20], [29, 29]], 4: [[11, 11], [29, 11], [11, 29], [29, 29]],
        5: [[11, 11], [29, 11], [20, 20], [11, 29], [29, 29]], 6: [[11, 11], [29, 11], [11, 20], [29, 20], [11, 29], [29, 29]]
    };

    function die(face) {
        return '<svg class="h-10 w-10 shrink-0 drop-shadow-sm" viewBox="0 0 40 40" aria-hidden="true"><rect x="1" y="1" width="38" height="38" rx="10" class="fill-white stroke-stone-300" stroke-width="1.5"/>' +
            PIPS[face].map(function (pip) { return '<circle cx="' + pip[0] + '" cy="' + pip[1] + '" r="3" class="fill-brand-700"/>'; }).join('') + '</svg>';
    }

    // ---- The upper bonus: what it needs, in words ---------------------------------------------------------------------

    var WORDS = ['No', 'One', 'Two', 'Three', 'Four', 'Five'];

    // need is 63 less the upper score, remaining the upper rows still to score ({label, face})
    function bonusTip(need, remaining) {
        // Only just made it, or only just missed it: worth a little fun
        if (need === 0) { return 'Exactly 63! Scraped it by the skin of your teeth, that is 50 extra points.'; }
        if (need < 0 && need >= -2) { return 'Just over the line and 50 extra points richer, smooth.'; }
        if (need <= 0) { return 'Upper bonus scored, that is 50 extra points.'; }
        if (remaining.length === 0 && need === 1) { return 'One point short of the bonus! So close you could taste it, someone has been robbed.'; }
        if (remaining.length === 0 && need === 2) { return 'Two points short of the bonus, it slipped right through your fingers.'; }
        if (remaining.length === 0) { return 'No upper bonus this time, you finished ' + need + ' short.'; }

        var faces = remaining.reduce(function (total, item) { return total + item.face; }, 0);

        for (var count = 1; count <= 5; count++) {
            if (count * faces >= need) {
                if (remaining.length === 1) {
                    return WORDS[count] + ' ' + remaining[0].label.toLowerCase() + ' would get you the bonus.';
                }

                var names = remaining.map(function (item) { return item.label.toLowerCase(); });

                return WORDS[count] + ' of each of the ' + names.slice(0, -1).join(', ') + ' and ' + names[names.length - 1] + ' left would get you the bonus.';
            }
        }

        return 'The upper bonus is out of reach now, focus on the lower section.';
    }

    // ---- The sheet: a native <dialog>, a bottom sheet on a phone and a centred dialog from sm up --------------------------
    //
    // A page can write its own <dialog class="sheet" id="..."> and open it with data-dialog-open="id", this builds the one
    // a script needs (the score entry).

    var Sheet = (function () {
        var dialog, heading, subheading, content, onClose;

        function build() {
            dialog = document.createElement('dialog');
            dialog.className = 'sheet';
            dialog.setAttribute('aria-labelledby', 'sheet-title');
            dialog.innerHTML = '<div class="p-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-6">' +
                '<div class="mx-auto mb-4 h-1 w-10 rounded-full bg-stone-200 sm:hidden"></div>' +
                '<div class="flex items-start justify-between gap-4">' +
                '<div class="min-w-0"><h2 id="sheet-title" class="text-xl font-extrabold tracking-tight"></h2><p id="sheet-subtitle" class="mt-0.5 text-sm text-stone-600"></p></div>' +
                '<button type="button" data-dialog-close class="-mr-3 -mt-2.5 shrink-0 rounded-full p-3 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="Close">' + icon('close', 'h-5 w-5') + '</button>' +
                '</div><div id="sheet-body" class="mt-5"></div></div>';
            document.body.appendChild(dialog);

            heading = dialog.querySelector('#sheet-title');
            subheading = dialog.querySelector('#sheet-subtitle');
            content = dialog.querySelector('#sheet-body');

            dialog.addEventListener('close', function () {
                var callback = onClose;
                onClose = null;
                if (callback) { callback(); }
            });
        }

        function open(options) {
            if (!dialog) { build(); }

            heading.textContent = options.title;
            subheading.textContent = options.subtitle || '';
            subheading.hidden = !options.subtitle;
            content.innerHTML = options.body;
            onClose = options.onClose || null;

            if (!dialog.open) { dialog.showModal(); }

            var focus = content.querySelector(options.focus || '[data-autofocus]') || content.querySelector('input, button');
            if (focus) { focus.focus(); }

            return content;
        }

        function close() {
            if (dialog && dialog.open) { dialog.close(); }
        }

        return {open: open, close: close, body: function () { return content; }};
    })();

    // ---- The snackbar: a short message with an optional action, such as Undo -------------------------------------------

    var Snack = (function () {
        var element, text, action, timer, handler;

        function bottomClasses() {
            // A page with the tab bar lifts the snackbar clear of it on a phone
            return document.querySelector('[data-tab-bar]') ? 'bottom-24 sm:bottom-6' : 'bottom-6';
        }

        function build() {
            element = document.createElement('div');
            element.className = 'pointer-events-none fixed inset-x-0 z-[70] flex justify-center px-4 opacity-0 transition-opacity duration-200 motion-reduce:transition-none ' + bottomClasses();
            element.setAttribute('role', 'status');
            element.setAttribute('aria-live', 'polite');
            element.innerHTML = '<div class="pointer-events-auto flex max-w-md items-center gap-3 rounded-2xl bg-stone-900 py-2.5 pl-4 pr-2.5 text-sm font-medium text-white shadow-lift">' +
                '<span data-snack-text></span>' +
                '<button type="button" data-snack-action class="min-h-10 rounded-lg px-3 font-bold text-brand-300 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-brand-300"></button></div>';
            document.body.appendChild(element);

            text = element.querySelector('[data-snack-text]');
            action = element.querySelector('[data-snack-action]');
            action.addEventListener('click', function () {
                var callback = handler;
                hide();
                if (callback) { callback(); }
            });
        }

        function hide() {
            clearTimeout(timer);
            handler = null;
            if (element) { element.classList.add('opacity-0'); }
        }

        function show(message, options) {
            if (!element) { build(); }

            options = options || {};
            clearTimeout(timer);

            text.textContent = message;
            handler = options.onAction || null;
            action.textContent = options.action || '';
            action.hidden = !options.action;
            element.classList.remove('opacity-0');
            timer = setTimeout(hide, options.duration || 6000);
        }

        return {show: show, hide: hide};
    })();

    // ---- Confetti: short, quiet and skipped for anyone who asks for reduced motion -------------------------------------

    function confetti() {
        if (prefersReducedMotion()) { return; }

        var canvas = document.createElement('canvas');
        canvas.className = 'pointer-events-none fixed inset-0 z-[80] h-full w-full';
        canvas.setAttribute('aria-hidden', 'true');

        var scale = window.devicePixelRatio || 1;
        canvas.width = window.innerWidth * scale;
        canvas.height = window.innerHeight * scale;
        document.body.appendChild(canvas);

        var context = canvas.getContext('2d');
        var colours = ['#03B1B8', '#82E0E5', '#FBBF24', '#FB7185', '#38BDF8', '#A3E635'];
        var pieces = [];

        for (var i = 0; i < 70; i++) {
            var angle = (-90 + (Math.random() - 0.5) * 100) * Math.PI / 180;
            var speed = 7 + Math.random() * 9;

            pieces.push({
                x: window.innerWidth / 2, y: window.innerHeight * 0.62,
                vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed,
                size: 5 + Math.random() * 6, rotation: Math.random() * Math.PI, spin: (Math.random() - 0.5) * 0.4,
                colour: colours[i % colours.length], round: i % 3 === 0
            });
        }

        var start = null;

        function frame(now) {
            if (start === null) { start = now; }

            var elapsed = now - start;
            context.setTransform(scale, 0, 0, scale, 0, 0);
            context.clearRect(0, 0, window.innerWidth, window.innerHeight);

            pieces.forEach(function (piece) {
                piece.vy += 0.32;
                piece.vx *= 0.992;
                piece.x += piece.vx;
                piece.y += piece.vy;
                piece.rotation += piece.spin;

                context.save();
                context.globalAlpha = Math.max(0, 1 - Math.max(0, elapsed - 1100) / 700);
                context.translate(piece.x, piece.y);
                context.rotate(piece.rotation);
                context.fillStyle = piece.colour;

                if (piece.round) {
                    context.beginPath();
                    context.arc(0, 0, piece.size / 2, 0, Math.PI * 2);
                    context.fill();
                } else {
                    context.fillRect(-piece.size / 2, -piece.size / 4, piece.size, piece.size / 2);
                }

                context.restore();
            });

            if (elapsed < 1800) { window.requestAnimationFrame(frame); } else { canvas.remove(); }
        }

        window.requestAnimationFrame(frame);
    }

    // ---- Copying a link ----------------------------------------------------------------------------------------------

    function copyText(value) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(value);
        }

        // An http page (a local copy of the app) has no clipboard API, fall back to selecting a hidden field
        return new Promise(function (resolve, reject) {
            var field = document.createElement('textarea');
            field.value = value;
            field.setAttribute('readonly', '');
            field.style.position = 'fixed';
            field.style.opacity = '0';
            document.body.appendChild(field);
            field.select();

            var copied = false;
            try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
            field.remove();

            if (copied) { resolve(); } else { reject(new Error('Copy is not available')); }
        });
    }

    // ---- Page behaviour, wired by data attributes ---------------------------------------------------------------------

    document.addEventListener('click', function (event) {
        var target = event.target;
        var element;

        // <button data-dialog-open="id"> opens the <dialog id="id">
        if ((element = target.closest('[data-dialog-open]'))) {
            var dialog = document.getElementById(element.dataset.dialogOpen);
            if (dialog && !dialog.open) {
                // A button inside an options menu closes the menu first
                var menu = element.closest('details[data-menu]');
                if (menu) { menu.open = false; }

                dialog.showModal();
                var focus = dialog.querySelector('[data-autofocus]');
                if (focus) { focus.focus(); }
            }
            return;
        }

        // Close buttons, and a click outside the panel which lands on the dialog itself (its backdrop)
        if (target.closest('[data-dialog-close]')) {
            var open = target.closest('dialog');
            if (open) { open.close(); }
            return;
        }

        if (target.tagName === 'DIALOG' && target.classList.contains('sheet')) {
            target.close();
            return;
        }

        // <button data-copy="https://..."> copies the link and says so
        if ((element = target.closest('[data-copy]'))) {
            var label = element.querySelector('[data-copy-label]');
            var original = label ? label.textContent : '';

            copyText(element.dataset.copy).then(function () {
                if (label) { label.textContent = 'Copied'; }
                element.classList.add('bg-emerald-50', 'text-emerald-800');
                setTimeout(function () {
                    if (label) { label.textContent = original; }
                    element.classList.remove('bg-emerald-50', 'text-emerald-800');
                }, 1800);
            }).catch(function () {
                // Show the link so it can be copied by hand
                window.prompt('Copy this link', element.dataset.copy);
            });
            return;
        }

        // An options menu closes when anything else is clicked
        document.querySelectorAll('details[data-menu][open]').forEach(function (details) {
            if (!details.contains(target)) { details.open = false; }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('details[data-menu][open]').forEach(function (details) { details.open = false; });
        }
    });

    // The player picker: checkboxes styled as chips, the start button says how many are chosen
    function updatePicker(form) {
        var boxes = form.querySelectorAll('input[type="checkbox"][name="players[]"]');
        var button = form.querySelector('[data-picker-submit]');
        var count = 0;

        boxes.forEach(function (box) { if (box.checked) { count++; } });
        if (!button) { return; }

        var minimum = parseInt(form.dataset.pickerMin || '1', 10);
        button.disabled = count < minimum;

        if (count < minimum) {
            button.textContent = minimum === 1 ? 'Choose the players to start' : 'Choose at least ' + minimum + ' players';
        } else {
            button.textContent = (form.dataset.pickerLabel || 'Start game with') + ' ' + count + (count === 1 ? ' player' : ' players');
        }
    }

    document.addEventListener('change', function (event) {
        var form = event.target.closest('form[data-picker]');
        if (form) { updatePicker(form); }
    });

    document.querySelectorAll('form[data-picker]').forEach(updatePicker);

    window.UI = {
        icon: icon, avatar: avatar, ring: ring, escape: escapeHtml, tones: TONES,
        Sheet: Sheet, Snack: Snack, bonusTip: bonusTip, die: die, confetti: confetti, copy: copyText, reducedMotion: prefersReducedMotion
    };
})();
