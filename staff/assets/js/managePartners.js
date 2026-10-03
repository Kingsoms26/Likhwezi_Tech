// managePartners.js runs the partner dialog and reordering on the partners tab of manageContent.php

// add and edit partner dialog
(() => {
    const dialog = document.getElementById('partner-dialog');
    const field = (id) => document.getElementById(id);
    const logoHint = dialog.querySelector('.photo-dropzone').closest('.form-field').querySelector('label .field-hint');

    // fill in the dialog for a partner or a new one and open it
    const open = (partner) => {
        dialog.querySelector('.dialog-errors').hidden = true;
        dialog.querySelector('.photo-dropzone-remove').click();

        field('partner-id').value = partner ? partner.id : '';
        field('partner-name').value = partner ? partner.name : '';
        field('partner-description').value = partner ? partner.description : '';
        field('partner-website').value = partner ? partner.websiteURL : '';

        field('partner-dialog-title').textContent = partner ? 'Edit partner' : 'Add a partner';
        field('partner-submit').textContent = partner ? 'Save changes' : 'Add partner';
        logoHint.textContent = partner ? '(leave empty to keep the current logo)' : '(required)';

        field('partner-current-logo').hidden = !partner;
        if (partner) field('partner-current-logo').querySelector('img').src = partner.logo;

        dialog.showModal();
        field('partner-name').focus();
    };

    // add and edit buttons
    field('partner-add').addEventListener('click', () => open(null));

    document.querySelectorAll('.partner-edit').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            open(JSON.parse(link.dataset.partner));
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

// reorder partners by dragging the handle or using the arrows
(() => {
    const tbody = document.querySelector('.partners-table tbody');
    const bar = document.getElementById('partner-order-bar');
    if (!tbody || !bar) return;

    const rows = () => [...tbody.querySelectorAll('tr[data-partner-id]')];
    const initialRows = rows();
    const orderKey = (list) => list.map((row) => row.dataset.partnerId).join();
    const initialKey = orderKey(initialRows);
    let saving = false;

    // renumber the rows and show the save bar when the order changed
    const refresh = () => {
        const list = rows();
        list.forEach((row, index) => {
            row.querySelector('.partner-position').textContent = index + 1;
            row.querySelector('[data-move="up"]').disabled = index === 0;
            row.querySelector('[data-move="down"]').disabled = index === list.length - 1;
        });
        bar.hidden = orderKey(list) === initialKey;
    };

    // arrow buttons
    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('.order-move');
        if (!button) return;

        const row = button.closest('tr');
        if (button.dataset.move === 'up' && row.previousElementSibling) {
            row.previousElementSibling.before(row);
        }
        if (button.dataset.move === 'down' && row.nextElementSibling?.dataset.partnerId) {
            row.nextElementSibling.after(row);
        }

        refresh();
        // keep focus on the row that moved even if this button is now disabled
        (button.disabled ? row.querySelector('.order-move:not(:disabled)') : button)?.focus();
    });

    // dragging only starts from the handle so text in the row can still be selected
    let dragging = null;

    tbody.addEventListener('pointerdown', (event) => {
        const handle = event.target.closest('.drag-handle');
        if (handle) handle.closest('tr').draggable = true;
    });

    // a click on the handle without dragging leaves the row as it was
    tbody.addEventListener('pointerup', () => {
        if (!dragging) rows().forEach((row) => { row.draggable = false; });
    });

    // pick up the row
    tbody.addEventListener('dragstart', (event) => {
        dragging = event.target.closest('tr[data-partner-id]');
        if (!dragging) return;
        dragging.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', dragging.dataset.partnerId);
    });

    // move the row above or below the one it is over
    tbody.addEventListener('dragover', (event) => {
        if (!dragging) return;
        event.preventDefault();

        const over = event.target.closest('tr[data-partner-id]');
        if (!over || over === dragging) return;

        const box = over.getBoundingClientRect();
        if (event.clientY > box.top + box.height / 2) {
            over.after(dragging);
        } else {
            over.before(dragging);
        }
    });

    tbody.addEventListener('drop', (event) => event.preventDefault());

    // drop the row
    tbody.addEventListener('dragend', () => {
        if (!dragging) return;
        dragging.classList.remove('is-dragging');
        dragging.draggable = false;
        dragging = null;
        refresh();
    });

    // undo puts the rows back in the order the page loaded with
    document.getElementById('partner-order-undo').addEventListener('click', () => {
        const firstArchived = tbody.querySelector('tr:not([data-partner-id])');
        initialRows.forEach((row) => tbody.insertBefore(row, firstArchived));
        refresh();
    });

    // send the new order
    document.getElementById('partner-order-form').addEventListener('submit', (event) => {
        const form = event.target;
        form.querySelectorAll('input[name="order[]"]').forEach((input) => input.remove());

        rows().forEach((row) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order[]';
            input.value = row.dataset.partnerId;
            form.append(input);
        });

        saving = true;
    });

    // warn before leaving with an unsaved order
    window.addEventListener('beforeunload', (event) => {
        if (!saving && !bar.hidden) event.preventDefault();
    });

    refresh();
})();
