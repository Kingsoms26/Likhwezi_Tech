// photoCarousel.js runs the photo carousels from components/photoCarousel.php

// start each carousel and keep it playing after visitors use the arrows, dots, swipe or drag
// set up once even if the component is on the page more than once
window.addEventListener('load', function () {
    if (!window.bootstrap) return;

    document.querySelectorAll('.photo-carousel:not([data-ready])').forEach(function (carouselElement) {
        carouselElement.dataset.ready = '1';

        // with reduced motion turned on the photos swap without sliding and change more slowly
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const carousel = bootstrap.Carousel.getOrCreateInstance(carouselElement, {
            interval: reduceMotion ? 7000 : 5000,
            ride: 'carousel',
            pause: false,
            touch: true
        });
        carousel.cycle();

        // bootstrap only handles touch swipes so mouse drags are added here
        let dragStartX = null;
        carouselElement.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button === 0) dragStartX = event.clientX;
        });
        window.addEventListener('pointerup', function (event) {
            if (dragStartX === null) return;
            const distance = event.clientX - dragStartX;
            dragStartX = null;
            if (Math.abs(distance) > 40) {
                distance < 0 ? carousel.next() : carousel.prev();
            }
        });

        // sideways trackpad swipes move one photo at a time
        let wheelLocked = false;
        carouselElement.addEventListener('wheel', function (event) {
            if (Math.abs(event.deltaX) <= Math.abs(event.deltaY) || Math.abs(event.deltaX) < 15) return;
            event.preventDefault();
            if (wheelLocked) return;
            wheelLocked = true;
            event.deltaX > 0 ? carousel.next() : carousel.prev();
            setTimeout(function () { wheelLocked = false; }, 700);
        }, { passive: false });
    });
});
