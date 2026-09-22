<!-- Expanded event modal: opened by clicking any .event-card (see renderEventCards in tools/events.php) -->
<div class="gallery-modal" id="eventModal" aria-hidden="true">

    <div class="gallery-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

        <button type="button" class="btn-close gallery-modal-close" id="closeEventModal" aria-label="Close event details"></button>

        <img id="modalImage" class="gallery-modal-image" src="images/placeholder.webp" alt="">

        <div class="gallery-modal-body">
<h2 id="modalTitle"></h2>
            <p id="modalMeta" ></p>
            <p id="modalDescription"></p>
        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('eventModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalImage = document.getElementById('modalImage');
        const modalMeta = document.getElementById('modalMeta');
        const modalDescription = document.getElementById('modalDescription');
        const closeButton = document.getElementById('closeEventModal');
        const eventCards = document.querySelectorAll('.event-card');

        function openEvent(card) {
            modalTitle.textContent = card.dataset.title;
            modalImage.src = card.dataset.image;
            modalImage.alt = card.dataset.title;
            modalMeta.textContent = card.dataset.date;
            modalDescription.textContent = card.dataset.description;

            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('gallery-modal-open');
            closeButton.focus();
        }

        function closeEvent() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('gallery-modal-open');
        }

        eventCards.forEach(function (card) {
            const button = card.querySelector('.event-card-button');
            button.addEventListener('click', function () {
                openEvent(card);
            });
        });

        closeButton.addEventListener('click', closeEvent);

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeEvent();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                closeEvent();
            }
        });
    });
</script>
