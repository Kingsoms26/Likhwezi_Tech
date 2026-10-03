// manageContent.js runs the add and edit dialog on manageContent.php

// add and edit dialog for programmes, services and photos, add opens it empty and edit fills it in
(() => {
    const dialog = document.getElementById('content-dialog');
    if (!dialog) return;

    // keep the icon preview in step with the chosen icon
    const iconSelect = document.getElementById('content-icon');
    const iconPreview = document.getElementById('content-icon-preview');
    const syncIcon = () => { if (iconSelect) iconPreview.className = 'bi ' + iconSelect.value + ' content-service-icon'; };
    iconSelect?.addEventListener('change', syncIcon);

    // fill in the dialog and open it
    const open = (values) => {
        const editing = Boolean(values.id);

        dialog.querySelector('.dialog-errors').hidden = true;
        dialog.querySelector('.photo-dropzone-remove')?.click();

        dialog.querySelectorAll('[data-field]').forEach((field) => {
            field.value = values[field.name] ?? field.dataset.default ?? '';
        });
        syncIcon();

        document.getElementById('content-dialog-title').textContent = editing ? dialog.dataset.editTitle : dialog.dataset.addTitle;
        dialog.querySelector('[data-dialog-submit]').textContent = editing ? 'Save changes' : dialog.dataset.addSubmit;

        dialog.querySelectorAll('[data-edit-only]').forEach((element) => { element.hidden = !editing; });
        const currentPhoto = dialog.querySelector('[data-current-photo]');
        if (currentPhoto) currentPhoto.src = values.src || '';

        const photoHint = dialog.querySelector('.photo-dropzone')?.closest('.form-field').querySelector('label .field-hint');
        if (photoHint) photoHint.textContent = editing ? '(leave empty to keep the current photo)' : '(required)';

        dialog.showModal();
        dialog.querySelector('input[type="text"]')?.focus();
    };

    // add and edit buttons
    document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            open(trigger.dataset.values ? JSON.parse(trigger.dataset.values) : {});
        });
    });

    // close buttons
    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    // clicking outside the dialog closes it
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });

    // remove edit from the address so a refresh does not reopen it
    dialog.addEventListener('close', () => {
        if (location.search.includes('edit=')) history.replaceState(null, '', dialog.dataset.tabUrl);
    });

    // open straight away after a failed save or an edit link
    if (dialog.dataset.openOnLoad) dialog.showModal();
})();
