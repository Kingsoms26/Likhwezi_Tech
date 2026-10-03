// archive.js runs the restore and delete popups on archive.php

// the row buttons and the selected items bar both open the same confirmation popup
document.addEventListener('DOMContentLoaded', function () {

    const modalElement = document.getElementById('archive-confirm');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const title = document.getElementById('archive-confirm-title');
    const message = document.getElementById('archive-confirm-message');
    const warning = document.getElementById('archive-confirm-warning');
    const actionInput = document.getElementById('archive-confirm-action');
    const itemsBox = document.getElementById('archive-confirm-items');
    const submit = document.getElementById('archive-confirm-submit');

    // fill in the confirmation popup for the chosen items and open it
    function openConfirm(action, items, note) {
        const isDelete = action === 'delete';
        const what = items.length === 1 ? '"' + items[0].name + '"' : items.length + ' items';

        title.textContent = isDelete ? 'Delete permanently?' : 'Restore from archive?';
        message.textContent = isDelete
            ? 'Permanently delete ' + what + '?' + (note ? ' ' + note : '')
            : 'Restore ' + what + '? It will show on its own page again.';
        warning.hidden = !isDelete;

        actionInput.value = action;
        itemsBox.replaceChildren(...items.map(function (item) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = item.key;
            return input;
        }));

        submit.textContent = isDelete ? 'Delete permanently' : 'Restore';
        submit.classList.toggle('button-danger', isDelete);
        submit.classList.toggle('button-primary', !isDelete);

        modal.show();
    }

    // restore and delete buttons on each row
    document.querySelectorAll('.js-archive-action').forEach(function (button) {
        button.addEventListener('click', function () {
            openConfirm(button.dataset.action, [{ key: button.dataset.item, name: button.dataset.name }], button.dataset.note);
        });
    });

    // ticking items

    const selectAll = document.getElementById('archive-select-all');
    const checkboxes = Array.from(document.querySelectorAll('.js-archive-item'));
    const bulkBar = document.getElementById('archive-bulk-bar');
    const selectedCount = document.getElementById('archive-selected-count');

    if (!selectAll) return;

    function selected() {
        return checkboxes.filter(function (box) { return box.checked; });
    }

    // show the selected items bar and keep the select all box in step
    function updateBulkBar() {
        const count = selected().length;
        bulkBar.hidden = count === 0;
        selectedCount.textContent = count + ' selected';
        selectAll.checked = count === checkboxes.length;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }

    // select all
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (box) { box.checked = selectAll.checked; });
        updateBulkBar();
    });

    checkboxes.forEach(function (box) { box.addEventListener('change', updateBulkBar); });

    // restore or delete the selected items
    document.querySelectorAll('.js-archive-bulk').forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.dataset.action;
            let boxes = selected();
            let note = '';

            // campaigns with donations are skipped when deleting, the server refuses them too
            if (action === 'delete') {
                const blocked = boxes.filter(function (box) { return box.dataset.deleteBlocked === '1'; });
                boxes = boxes.filter(function (box) { return box.dataset.deleteBlocked !== '1'; });
                if (blocked.length) {
                    note = blocked.length + (blocked.length === 1 ? ' campaign has' : ' campaigns have')
                        + ' donations and will be kept.';
                }
                if (!boxes.length) {
                    alert('The selected campaigns have donations, so they cannot be deleted.');
                    return;
                }
                note = (note ? note + ' ' : '') + 'Any photos belonging to these items will also be deleted.';
            }

            openConfirm(action, boxes.map(function (box) {
                return { key: box.value, name: box.dataset.name };
            }), note);
        });
    });

});
