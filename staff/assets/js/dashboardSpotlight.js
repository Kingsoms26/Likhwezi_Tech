document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dashboard-spotlight]').forEach((carousel) => {
        const slides = Array.from(carousel.querySelectorAll('[data-spotlight-slide]'));
        const dots = Array.from(carousel.querySelectorAll('[data-spotlight-go]'));
        const counter = carousel.querySelector('[data-spotlight-counter]');
        const previous = carousel.querySelector('[data-spotlight-prev]');
        const next = carousel.querySelector('[data-spotlight-next]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let current = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
        let timer = null;

        if (slides.length < 2) return;

        const showSlide = (index) => {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === current;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
            dots.forEach((dot, dotIndex) => {
                const active = dotIndex === current;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            if (counter) counter.textContent = `${String(current + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
        };

        const stopTimer = () => {
            if (timer !== null) window.clearInterval(timer);
            timer = null;
        };

        const startTimer = () => {
            stopTimer();
            if (!reducedMotion && !carousel.matches(':hover') && !carousel.contains(document.activeElement)) {
                timer = window.setInterval(() => showSlide(current + 1), 6500);
            }
        };

        dots.forEach((dot) => dot.addEventListener('click', () => {
            showSlide(Number(dot.dataset.spotlightGo));
            startTimer();
        }));
        previous?.addEventListener('click', () => { showSlide(current - 1); startTimer(); });
        next?.addEventListener('click', () => { showSlide(current + 1); startTimer(); });
        carousel.addEventListener('mouseenter', stopTimer);
        carousel.addEventListener('mouseleave', startTimer);
        carousel.addEventListener('focusin', stopTimer);
        carousel.addEventListener('focusout', (event) => {
            if (!carousel.contains(event.relatedTarget)) startTimer();
        });
        document.addEventListener('visibilitychange', () => document.hidden ? stopTimer() : startTimer());
        showSlide(current);
        startTimer();
    });
});
