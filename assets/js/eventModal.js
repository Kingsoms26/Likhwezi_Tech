// eventModal.js runs the event popup in components/eventModal.php

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalImage = document.getElementById('modalImage');
    const modalMeta = document.getElementById('modalMeta');
    const modalDescription = document.getElementById('modalDescription');
    const closeButton = document.getElementById('closeEventModal');
    const eventCards = document.querySelectorAll('.event-card');

    const viewer = document.getElementById('modalViewer');
    const prevButton = document.getElementById('modalPrev');
    const nextButton = document.getElementById('modalNext');
    const counter = document.getElementById('modalCounter');
    const backdrop = document.getElementById('modalBackdrop');

    // zoom limits
    const MAX_SCALE = 4;
    const ZOOM_STEP = 2.5;

    // the open event's photos
    let images = [];
    let index = 0;
    let title = '';

    // how far the photo is zoomed and moved
    let scale = 1;
    let x = 0;
    let y = 0;

    // zoom and move

    // keep the zoomed photo covering the viewer so it cannot be dragged off screen
    function clamp() {
        const maxX = viewer.clientWidth * (scale - 1) / 2;
        const maxY = viewer.clientHeight * (scale - 1) / 2;
        x = Math.min(maxX, Math.max(-maxX, x));
        y = Math.min(maxY, Math.max(-maxY, y));
    }

    // apply the zoom and position
    function applyTransform(animate) {
        modalImage.classList.toggle('is-animating', animate);
        modalImage.style.transform = 'translate(' + x + 'px, ' + y + 'px) scale(' + scale + ')';
        viewer.classList.toggle('is-zoomed', scale > 1);
    }

    // zoom while keeping the point under the finger or cursor in place
    function zoomTo(newScale, clientX, clientY, animate) {
        newScale = Math.min(MAX_SCALE, Math.max(1, newScale));
        const box = viewer.getBoundingClientRect();
        const px = (clientX ?? box.left + box.width / 2) - (box.left + box.width / 2);
        const py = (clientY ?? box.top + box.height / 2) - (box.top + box.height / 2);

        x = px - (px - x) * (newScale / scale);
        y = py - (py - y) * (newScale / scale);
        scale = newScale;

        if (scale === 1) {
            x = 0;
            y = 0;
        }

        clamp();
        applyTransform(animate);
    }

    // zoom back out
    function resetZoom() {
        scale = 1;
        x = 0;
        y = 0;
        applyTransform(false);
    }

    // ctrl and scroll or a trackpad pinch zooms
    viewer.addEventListener('wheel', function (event) {
        if (!event.ctrlKey && scale === 1) return;
        event.preventDefault();
        zoomTo(scale * Math.exp(-event.deltaY * 0.01), event.clientX, event.clientY, false);
    }, { passive: false });

    // swipe, drag, pinch and double tap

    const pointers = new Map();
    let gesture = null;
    let lastTap = { time: 0, x: 0, y: 0 };

    const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

    viewer.addEventListener('pointerdown', function (event) {
        viewer.setPointerCapture(event.pointerId);
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

        if (pointers.size === 2) {
            // second finger starts a pinch
            const [a, b] = [...pointers.values()];
            gesture = { type: 'pinch', startDistance: distance(a, b), startScale: scale };
        } else if (pointers.size === 1) {
            // one finger drags when zoomed in or swipes when not
            gesture = {
                type: scale > 1 ? 'pan' : 'swipe',
                startX: event.clientX,
                startY: event.clientY,
                originX: x,
                originY: y,
                moved: false,
            };
        }
    });

    viewer.addEventListener('pointermove', function (event) {
        if (!pointers.has(event.pointerId) || !gesture) return;
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

        if (gesture.type === 'pinch' && pointers.size === 2) {
            const [a, b] = [...pointers.values()];
            const midX = (a.x + b.x) / 2;
            const midY = (a.y + b.y) / 2;
            zoomTo(gesture.startScale * distance(a, b) / gesture.startDistance, midX, midY, false);
            return;
        }

        const dx = event.clientX - gesture.startX;
        const dy = event.clientY - gesture.startY;
        if (Math.abs(dx) > 6 || Math.abs(dy) > 6) gesture.moved = true;

        if (gesture.type === 'pan') {
            x = gesture.originX + dx;
            y = gesture.originY + dy;
            clamp();
            applyTransform(false);
        } else if (gesture.type === 'swipe' && images.length > 1) {
            // the photo follows the finger sideways until it is let go
            x = dx;
            applyTransform(false);
        }
    });

    function endPointer(event) {
        if (!pointers.has(event.pointerId)) return;
        pointers.delete(event.pointerId);

        if (!gesture) return;

        if (gesture.type === 'pinch') {
            // one finger still down after a pinch carries on as a drag
            if (pointers.size === 1) {
                const [p] = [...pointers.values()];
                gesture = { type: 'pan', startX: p.x, startY: p.y, originX: x, originY: y, moved: true };
            } else {
                gesture = null;
                if (scale < 1.05) zoomTo(1, null, null, true);
            }
            return;
        }

        const dx = event.clientX - gesture.startX;
        const dy = event.clientY - gesture.startY;

        // a long enough sideways swipe moves to the next or previous photo
        if (gesture.type === 'swipe') {
            const swiped = images.length > 1 && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy);
            x = 0;
            if (swiped) {
                show(index + (dx < 0 ? 1 : -1));
            } else {
                applyTransform(true);
            }
        }

        // two quick taps in the same place zoom in or out
        if (!gesture.moved && event.type === 'pointerup') {
            const now = Date.now();
            const near = Math.hypot(event.clientX - lastTap.x, event.clientY - lastTap.y) < 30;
            if (now - lastTap.time < 300 && near) {
                zoomTo(scale > 1 ? 1 : ZOOM_STEP, event.clientX, event.clientY, true);
                lastTap.time = 0;
            } else {
                lastTap = { time: now, x: event.clientX, y: event.clientY };
            }
        }

        gesture = null;
    }

    viewer.addEventListener('pointerup', endPointer);
    viewer.addEventListener('pointercancel', endPointer);

    // photos

    // show a photo and its counter
    function show(newIndex) {
        index = (newIndex + images.length) % images.length;
        resetZoom();

        modalImage.src = images[index];
        modalImage.alt = images.length > 1 ? title + ' (photo ' + (index + 1) + ' of ' + images.length + ')' : title;
        counter.textContent = (index + 1) + ' / ' + images.length;

        backdrop.style.backgroundImage = 'url(' + JSON.stringify(images[index]) + ')';

        // load the photos either side so moving to them is instant
        [index - 1, index + 1].forEach(function (i) {
            if (images.length > 1) new Image().src = images[(i + images.length) % images.length];
        });
    }

    // arrows only fade in when the mouse nears the left or right edge
    const media = viewer.parentElement;
    const EDGE_ZONE = 0.25;

    media.addEventListener('mousemove', function (event) {
        const box = media.getBoundingClientRect();
        const position = (event.clientX - box.left) / box.width;
        media.classList.toggle('near-prev', position < EDGE_ZONE);
        media.classList.toggle('near-next', position > 1 - EDGE_ZONE);
    });

    media.addEventListener('mouseleave', function () {
        media.classList.remove('near-prev', 'near-next');
    });

    // arrow buttons
    prevButton.addEventListener('click', function () {
        show(index - 1);
    });

    nextButton.addEventListener('click', function () {
        show(index + 1);
    });

    // open and close

    // fill in the popup from the card and open it
    function openEvent(card) {
        title = card.dataset.title;
        modalTitle.textContent = card.dataset.title;
        modalMeta.textContent = card.dataset.date;
        modalDescription.textContent = card.dataset.description;

        try {
            images = JSON.parse(card.dataset.images || '[]');
        } catch (error) {
            images = [];
        }
        if (!images.length) images = [card.dataset.image];

        // only show the arrows and counter when there is more than one photo
        const multiple = images.length > 1;
        modal.classList.toggle('has-multiple', multiple);
        prevButton.hidden = !multiple;
        nextButton.hidden = !multiple;
        counter.hidden = !multiple;

        show(0);

        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('gallery-modal-open');
        closeButton.focus();
    }

    // close the popup
    function closeEvent() {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('gallery-modal-open');
        resetZoom();
    }

    // clicking a card opens it
    eventCards.forEach(function (card) {
        const button = card.querySelector('.event-card-button');
        button.addEventListener('click', function () {
            openEvent(card);
        });
    });

    closeButton.addEventListener('click', closeEvent);

    // clicking outside the popup closes it
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeEvent();
        }
    });

    // escape and the arrow keys
    document.addEventListener('keydown', function (event) {
        if (modal.getAttribute('aria-hidden') !== 'false') return;

        if (event.key === 'Escape') {
            // the first escape zooms back out and the next one closes
            if (scale > 1) {
                zoomTo(1, null, null, true);
            } else {
                closeEvent();
            }
        } else if (event.key === 'ArrowLeft' && images.length > 1) {
            show(index - 1);
        } else if (event.key === 'ArrowRight' && images.length > 1) {
            show(index + 1);
        }
    });
});
