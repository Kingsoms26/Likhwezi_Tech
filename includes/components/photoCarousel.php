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
<script src="assets/js/photoCarousel.js?v=<?= filemtime(__DIR__ . '/../../assets/js/photoCarousel.js') ?>"></script>
