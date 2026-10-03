// manageEvents.js runs the dialogs on manageEvents.php

// close buttons, clicking outside the dialog also closes it
document.querySelectorAll('.dashboard-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
});

// add and edit dialog
(() => {
    const dialog = document.getElementById('event-dialog');
    const field = (id) => document.getElementById(id);

    // fill in the dialog for an event or a new one and open it
    const open = (event) => {
        dialog.querySelector('.dialog-errors').hidden = true;
        dialog.querySelector('.gallery-uploader').galleryReset();

        field('event-id').value = event ? event.id : '';
        field('event-name').value = event ? event.name : '';
        field('event-date').value = event ? event.eventDate : '';
        field(event && event.tbc ? 'event-undated-tbc' : 'event-undated-past').checked = true;
        syncUndated();
        field('event-description').value = event ? event.description : '';
        showPhotos(event ? event.photos : []);

        field('event-dialog-title').textContent = event ? 'Edit event' : 'Add an event';
        field('event-submit').textContent = event ? 'Save changes' : 'Add event';

        dialog.showModal();
        field('event-name').focus();
    };

    // the event's current photos, each with a remove tick box and the first is the cover
    const showPhotos = (photos) => {
        const template = field('event-photo-template').content.firstElementChild;
        field('event-current-photo-list').replaceChildren(...photos.map((photo, index) => {
            const item = template.cloneNode(true);
            item.querySelector('img').src = photo.src;
            item.querySelector('input').value = photo.id;
            if (index !== 0) item.querySelector('.gallery-cover-badge').remove();
            return item;
        }));
        field('event-current-photos').hidden = photos.length === 0;
    };

    // the to be confirmed or past choice only applies while there is no date
    const syncUndated = () => {
        field('event-undated').hidden = field('event-date').value !== '';
    };
    field('event-date').addEventListener('input', syncUndated);

    // add and edit buttons
    field('event-add')?.addEventListener('click', () => open(null));

    document.querySelectorAll('.event-edit').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            open(JSON.parse(link.dataset.event));
        });
    });

    // remove edit from the address so a refresh does not reopen it
    dialog.addEventListener('close', () => {
        const url = new URL(location.href);
        if (url.searchParams.has('edit')) {
            url.searchParams.delete('edit');
            history.replaceState(null, '', url);
        }
    });

    // open straight away after a failed save or an edit link
    if (dialog.dataset.openOnLoad) dialog.showModal();
})();
