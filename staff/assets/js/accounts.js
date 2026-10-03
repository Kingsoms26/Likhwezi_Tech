// accounts.js runs the account popups on accounts.php

// fill in each popup from the row that opened it, bootstrap handles the rest
document.addEventListener('DOMContentLoaded', function () {

    const editModal = document.getElementById('edit-account-modal');
    const manageModal = document.getElementById('manage-account-modal');

    // edit popup
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            editModal.querySelector('#edit-account-id').value = button.dataset.accountId || '';
            editModal.querySelector('#edit-username').value = button.dataset.username || '';
            editModal.querySelector('#edit-email').value = button.dataset.email || '';
            editModal.querySelector('#edit-role').value = button.dataset.role || '';
        });
    }

    // manage popup
    if (manageModal) {
        manageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            manageModal.querySelector('#manage-account-id').value = button.dataset.accountId || '';
            manageModal.querySelector('#manage-account-name').textContent = button.dataset.username || 'Account';
            manageModal.querySelector('#manage-status').value = button.dataset.status || 'active';
            manageModal.querySelector('#manage-password').value = '';
        });
    }

    // archive and restore only need the account's id and name
    ['archive', 'restore'].forEach(function (kind) {
        const modal = document.getElementById(kind + '-account-modal');
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            modal.querySelector('#' + kind + '-account-id').value = button.dataset.accountId || '';
            modal.querySelector('#' + kind + '-account-name').textContent = button.dataset.username || 'Account';
        });
    });

    // new account, open the login details straight away
    const credentialsModal = document.getElementById('new-credentials-modal');

    if (credentialsModal) {
        bootstrap.Modal.getOrCreateInstance(credentialsModal).show();

        const copyButton = document.getElementById('new-credentials-copy');
        const message = document.getElementById('new-credentials-message');

        // copy the message
        copyButton.addEventListener('click', function () {
            const done = function () {
                copyButton.textContent = 'Copied';
                setTimeout(function () { copyButton.textContent = 'Copy'; }, 2000);
            };

            // copying needs https or localhost, otherwise select the text and copy it
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(message.value).then(done);
            } else {
                message.select();
                document.execCommand('copy');
                done();
            }
        });
    }

});
