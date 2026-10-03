<!-- photoCarousel.php is the hero photo carousel on the cym and gallery pages
 plays on its own and visitors can use the arrows, dots, swipe, drag or a trackpad swipe
 needs $carouselID and $carouselPhotos, each photo has a src, alt and optional position to adjust the crop
-->
<div id="<?= htmlspecialchars($carouselID) ?>" class="carousel slide photo-carousel">
    <!-- dots -->
    <div class="carousel-indicators">
        <?php foreach ($carouselPhotos as $i => $photo): ?>
            <button type="button" data-bs-target="#<?= htmlspecialchars($carouselID) ?>" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Photo <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <!-- photos -->
    <div class="carousel-inner">
        <?php foreach ($carouselPhotos as $i => $photo): ?>
            <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                <img src="<?= htmlspecialchars($photo['src']) ?>" alt="<?= htmlspecialchars($photo['alt']) ?>" draggable="false"<?= $i === 0 ? '' : ' loading="lazy"' ?><?= !empty($photo['position']) ? ' style="object-position: ' . htmlspecialchars($photo['position']) . '"' : '' ?>>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- arrows, only when there is more than one photo -->
    <?php if (count($carouselPhotos) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?= htmlspecialchars($carouselID) ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?= htmlspecialchars($carouselID) ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    <?php endif; ?>
</div>
<script>
    // start the carousel and keep it playing after visitors use the arrows, dots, swipe or drag
    window.addEventListener('load', function () {
        const carouselElement = document.getElementById(<?= json_encode($carouselID, JSON_HEX_TAG) ?>);
        if (!carouselElement || !window.bootstrap) return;

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
</script>
