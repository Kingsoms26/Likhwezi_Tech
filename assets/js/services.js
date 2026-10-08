// services.js runs the scrolling service cards on services.php

// move the cards based on how far the visitor has scrolled through the section
(function () {
    const stack = document.getElementById('serviceStack');
    if (!stack) return;

    const cards = Array.from(stack.querySelectorAll('.stack-card'));
    const dots = Array.from(stack.querySelectorAll('.service-stack-dots span'));
    const last = cards.length - 1;
    let ticking = false;

    // screens too short to pin the cards get the plain card list instead
    const tallEnough = window.matchMedia('(min-height: 520px)');

    stack.style.setProperty('--stack-count', cards.length);

    // ease the position so scrolling glides instead of jumping
    let current = null;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // how far through the section the visitor is
    function targetProgress() {
        const rect = stack.getBoundingClientRect();
        const scrollable = stack.offsetHeight - window.innerHeight;
        const ratio = scrollable > 0 ? Math.min(Math.max(-rect.top / scrollable, 0), 1) : 0;
        return ratio * last;
    }

    // place each card and highlight the active dot
    function render(progress) {
        const active = Math.round(progress);

        cards.forEach(function (card, i) {
            // cards to the left have slid past and cards to the right are waiting
            const offset = i - progress;
            const distance = Math.min(Math.abs(offset), 1);
            card.style.transform = 'translateX(calc(-50% + ' + (offset * 106) + '%)) scale(' + (1 - distance * 0.08) + ')';
            card.style.opacity = 1 - distance * 0.2;
            card.classList.toggle('is-active', i === active);
        });

        dots.forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === active);
        });
    }

    // move towards the scroll position a little each frame
    function update() {
        ticking = false;
        if (!stack.classList.contains('is-stacked')) return;
        const target = targetProgress();
        current = current === null || reduceMotion ? target : current + (target - current) * 0.18;
        if (Math.abs(target - current) < 0.001) current = target;
        render(current);
        if (current !== target) requestUpdate();
    }

    function requestUpdate() {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(update);
        }
    }

    // switch between pinned cards and the plain list
    function setMode() {
        const stacked = tallEnough.matches;
        stack.classList.toggle('is-stacked', stacked);
        current = null;
        if (stacked) {
            requestUpdate();
        } else {
            cards.forEach(function (card) {
                card.style.transform = '';
                card.style.opacity = '';
            });
        }
    }

    if (tallEnough.addEventListener) {
        tallEnough.addEventListener('change', setMode);
    } else {
        tallEnough.addListener(setMode);
    }
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    setMode();

    // links from the home page like services.php#data-testing open on that card
    function showHashedCard() {
        const slug = window.location.hash.slice(1);
        const index = cards.findIndex(function (card) { return card.dataset.slug === slug; });
        if (index === -1) return;

        if (stack.classList.contains('is-stacked')) {
            const sectionTop = stack.getBoundingClientRect().top + window.scrollY;
            const scrollable = stack.offsetHeight - window.innerHeight;
            current = null;
            window.scrollTo(0, sectionTop + scrollable * (index / last));
        } else {
            cards[index].scrollIntoView();
        }
    }

    window.addEventListener('load', showHashedCard);
    window.addEventListener('hashchange', showHashedCard);
})();
