/*
 * The landing page: a score sheet to try (nothing is sent anywhere, it only lives on this page) and the walkthrough that
 * swaps the phone as the steps scroll past. The rules and the words are the real ones, the bonus tip comes from ui.js.
 */
(function () {
    'use strict';

    var UI = window.UI;

    // ---- Try the score sheet -----------------------------------------------------------------------------------------

    function demo(root) {
        var UPPER = [
            {id: 'ones', label: 'Ones', face: 1}, {id: 'twos', label: 'Twos', face: 2}, {id: 'threes', label: 'Threes', face: 3},
            {id: 'fours', label: 'Fours', face: 4}, {id: 'fives', label: 'Fives', face: 5}, {id: 'sixes', label: 'Sixes', face: 6}
        ];

        var counts = {};       // id -> how many dice were rolled, 0 is a scratch
        var open = null;       // the row asking how many
        var focus = null;      // the row to give focus back to after a redraw
        var previous = null;

        function part(name) { return root.querySelector('[data-demo="' + name + '"]'); }
        function find(id) { return UPPER.filter(function (item) { return item.id === id; })[0]; }
        function scored() { return UPPER.filter(function (item) { return counts[item.id] !== undefined; }); }
        function upper() { return scored().reduce(function (total, item) { return total + counts[item.id] * item.face; }, 0); }
        function bonus() { return upper() >= 63 ? 50 : 0; }

        function rowHtml(item) {
            var chosen = counts[item.id];
            var isScored = chosen !== undefined;
            var value = isScored ? chosen * item.face : 0;
            var right;

            if (!isScored) {
                right = '<span class="rounded-full bg-brand-50 px-4 py-1.5 text-sm font-bold text-brand-800 ring-1 ring-brand-100">Score</span>';
            } else if (value === 0) {
                right = '<span class="text-xs font-semibold text-stone-600">Scratched</span><span class="w-9 text-right text-xl font-extrabold tabular-nums text-stone-400">0</span>';
            } else {
                right = UI.icon('check-circle', 'h-5 w-5 text-emerald-600') + '<span class="sr-only">Scored</span><span class="w-9 text-right text-xl font-extrabold tabular-nums">' + value + '</span>';
            }

            var head = UI.die(item.face) +
                '<span class="min-w-0 flex-1"><span class="block font-bold ' + (isScored && value === 0 ? 'text-stone-600' : '') + '">' + item.label + '</span>' +
                '<span class="block text-xs text-stone-600">' + item.face + (item.face === 1 ? ' point' : ' points') + ' each</span></span>' +
                '<span class="flex shrink-0 items-center gap-2">' + right + '</span>';

            var attributes = 'type="button" data-row="' + item.id + '" aria-expanded="' + (open === item.id) + '" class="flex min-h-16 w-full items-center gap-3.5 px-4 py-2.5 text-left transition hover:bg-stone-50 active:bg-stone-100 focus-visible:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none"';

            if (open !== item.id) {
                return '<li><button ' + attributes + '>' + head + '</button></li>';
            }

            return '<li class="bg-brand-50/60"><button ' + attributes + '>' + head + '</button>' +
                '<div class="px-4 pb-4 pt-1"><p class="mb-2.5 text-sm text-stone-700">How many ' + item.label.toLowerCase() + ' did you roll? Tap 0 to scratch.</p>' +
                '<div class="grid grid-cols-6 gap-1.5" role="group" aria-label="Number of ' + item.label.toLowerCase() + '">' +
                [0, 1, 2, 3, 4, 5].map(function (count) {
                    return '<button type="button" data-count="' + count + '" aria-pressed="' + (chosen === count) + '" aria-label="' + (count === 0 ? 'Scratch' : count + ' ' + item.label.toLowerCase() + ', ' + count * item.face + ' points') + '" class="flex h-14 flex-col items-center justify-center rounded-xl ring-1 ring-inset transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none ' +
                        (chosen === count ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white ring-stone-300 hover:bg-brand-50 hover:ring-brand-300 active:bg-brand-100') + '">' +
                        '<span class="text-xl font-extrabold leading-none tabular-nums">' + count + '</span><span class="mt-0.5 text-[11px] font-semibold ' + (chosen === count ? 'text-brand-100' : 'text-stone-600') + '">' + (count === 0 ? 'Scratch' : '= ' + count * item.face) + '</span></button>';
                }).join('') + '</div></div></li>';
        }

        function render() {
            var keep = focus || (document.activeElement && document.activeElement.dataset && document.activeElement.dataset.row ? document.activeElement.dataset.row : null);
            var total = upper() + bonus(), done = scored().length, need = 63 - upper();

            part('list').innerHTML = UPPER.map(rowHtml).join('');

            part('total').textContent = total;
            if (previous !== null && previous !== total && !UI.reducedMotion() && part('total').animate) {
                part('total').animate([{transform: 'scale(1.16)'}, {transform: 'scale(1)'}], {duration: 260, easing: 'ease-out'});
            }
            previous = total;

            part('upper').textContent = upper();
            part('bonus').textContent = bonus();
            part('turns').textContent = '· ' + done + ' of ' + UPPER.length + ' rows';
            part('count').textContent = Math.min(upper(), 63);
            part('progress').setAttribute('aria-valuenow', Math.min(upper(), 63));
            part('bar').style.width = Math.min(100, Math.round(upper() / 63 * 100)) + '%';
            part('bar').className = 'h-full rounded-full transition-[width] duration-500 motion-reduce:transition-none ' + (need <= 0 ? 'bg-emerald-500' : 'bg-brand-600');
            part('tip-icon').innerHTML = UI.icon(need <= 0 ? 'sparkles' : 'bulb', 'h-5 w-5');
            part('tip').textContent = need === 63 && done === 0
                ? 'Score a row to see how it works. Three of each gets you the 50 point bonus.'
                : UI.bonusTip(need, UPPER.filter(function (item) { return counts[item.id] === undefined; }));

            part('done').hidden = done < UPPER.length;
            part('done-score').textContent = total;
            part('reset-bar').hidden = done === 0 || done === UPPER.length;

            if (keep) {
                var again = root.querySelector('[data-row="' + keep + '"]');
                if (again) { again.focus({preventScroll: true}); }
                focus = null;
            }
        }

        function reset() {
            counts = {};
            open = null;
            previous = null;
            render();
            part('list').querySelector('button').focus({preventScroll: true});
        }

        root.addEventListener('click', function (event) {
            var element;

            if ((element = event.target.closest('[data-count]'))) {
                var item = find(open), count = Number(element.dataset.count), before = bonus();

                counts[item.id] = count;
                focus = item.id;
                open = null;
                render();
                if (before === 0 && bonus() === 50) { UI.confetti(); }

                return;
            }

            if ((element = event.target.closest('[data-row]'))) {
                var id = element.dataset.row;

                open = open === id ? null : id;
                focus = id;
                render();

                // The numbers are what the person came for, move to the first one
                if (open) { root.querySelector('[data-count]').focus({preventScroll: true}); }

                return;
            }

            if (event.target.closest('[data-demo="reset"], [data-demo="again"]')) { reset(); }
        });

        root.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && open) {
                focus = open;
                open = null;
                render();
            }
        });

        render();
    }

    // ---- The walkthrough: the phone shows the step that is in the middle of the screen --------------------------------

    function walkthrough(root) {
        var steps = Array.prototype.slice.call(root.querySelectorAll('[data-step]'));
        var shots = Array.prototype.slice.call(root.querySelectorAll('[data-shot]'));
        var current = -1;

        function activate(index) {
            if (index === current) { return; }
            current = index;

            steps.forEach(function (step, position) {
                if (position === index) { step.setAttribute('data-active', ''); } else { step.removeAttribute('data-active'); }
            });
            shots.forEach(function (shot, position) {
                if (position === index) { shot.setAttribute('data-active', ''); shot.removeAttribute('aria-hidden'); } else { shot.removeAttribute('data-active'); shot.setAttribute('aria-hidden', 'true'); }
            });
        }

        // From here the stylesheet swaps the pictures under each step for the one phone beside them
        root.setAttribute('data-js', '');
        activate(0);

        if (!('IntersectionObserver' in window)) { return; }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { activate(steps.indexOf(entry.target)); }
            });
        }, {rootMargin: '-45% 0px -45% 0px'});

        steps.forEach(function (step) { observer.observe(step); });
    }

    var tryIt = document.getElementById('demo');
    if (tryIt) { demo(tryIt); }

    var walk = document.getElementById('walk');
    if (walk) { walkthrough(walk); }
})();
