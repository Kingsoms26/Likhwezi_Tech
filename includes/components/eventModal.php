<!-- eventModal.php is the event details popup, opened by clicking any event card
 contains a photo viewer with every photo of the event, cover first
 move between photos with the arrows, keyboard or a swipe
 zoom with a double tap, pinch or ctrl and scroll then drag to move around
-->
<div class="gallery-modal" id="eventModal" aria-hidden="true">

    <div class="gallery-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

        <button type="button" class="btn-close gallery-modal-close" id="closeEventModal" aria-label="Close event details"></button>

        <!-- photo viewer -->
        <div class="gallery-modal-media">
            <div class="gallery-viewer" id="modalViewer">
                <!-- blurred copy of the photo fills the space around it so nothing is cropped -->
                <div class="gallery-viewer-backdrop" id="modalBackdrop" aria-hidden="true"></div>
                <img id="modalImage" class="gallery-modal-image" src="assets/images/placeholder.webp" alt="" draggable="false">
            </div>

            <!-- previous and next arrows and the photo counter -->
            <button type="button" class="gallery-viewer-nav gallery-viewer-prev" id="modalPrev" aria-label="Previous photo">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="gallery-viewer-nav gallery-viewer-next" id="modalNext" aria-label="Next photo">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>

            <span class="gallery-viewer-counter" id="modalCounter" aria-live="polite"></span>
        </div>

        <!-- event title, date and description -->
        <div class="gallery-modal-body">
            <h2 id="modalTitle"></h2>
            <p id="modalMeta" ></p>
            <p id="modalDescription"></p>
        </div>

    </div>

</div>

<script src="assets/js/eventModal.js?v=<?= filemtime(__DIR__ . '/../../assets/js/eventModal.js') ?>"></script>
