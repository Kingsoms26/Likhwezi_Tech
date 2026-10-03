// manageCampaigns.js runs the dialogs and popups on manageCampaigns.php

// close buttons for both dialogs, clicking outside also closes them
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
    const dialog = document.getElementById('campaign-dialog');
    const field = (id) => document.getElementById(id);

    // fill in the dialog for a campaign or a new one and open it
    const open = (campaign) => {
        dialog.querySelector('.dialog-errors').hidden = true;
        dialog.querySelector('.gallery-uploader').galleryReset();

        field('campaign-id').value = campaign ? campaign.id : '';
        field('campaign-name').value = campaign ? campaign.name : '';
        field('campaign-goal').value = campaign ? campaign.goal : '';
        field('campaign-status-' + (campaign ? campaign.status : 'draft')).checked = true;
        field('campaign-description').value = campaign ? campaign.description : '';

        field('campaign-dialog-title').textContent = campaign ? 'Edit campaign' : 'Add a campaign';
        field('campaign-submit').textContent = campaign ? 'Save changes' : 'Add campaign';

        dialog.showModal();
        field('campaign-name').focus();
    };

    // add and edit buttons
    field('campaign-add')?.addEventListener('click', () => open(null));

    document.querySelectorAll('.campaign-edit').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            open(JSON.parse(link.dataset.campaign));
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

// close, archive and restore confirmation, waits for bootstrap to load
document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('campaign-confirm');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    // wording for each action
    const texts = {
        close: (name) => ['Close this campaign?',
            name + ' will be removed from the website and stop taking donations. Its donations are kept. You can reopen it later by editing its status.',
            'Close campaign', true],
        archive: (name) => ['Archive this campaign?',
            name + ' will be hidden from the website and stop taking donations. Its donations are kept, and it can be restored later.',
            'Archive', true],
        restore: (name, status) => ['Restore this campaign?',
            name + ' will move back to the ' + status + ' campaigns.'
                + (status === 'active' ? ' It will show on the website and take donations again.' : ''),
            'Restore', false],
    };

    // fill in the confirmation and open it
    document.addEventListener('click', (event) => {
        const button = event.target.closest('.js-campaign-confirm');
        if (!button) return;

        const kind = button.dataset.action === 'close' ? 'close' : (button.dataset.archived === '1' ? 'restore' : 'archive');
        const [title, message, label, danger] = texts[kind](button.dataset.name, button.dataset.status);

        element.querySelector('.modal-title').textContent = title;
        element.querySelector('.confirm-message').textContent = message;

        const submit = element.querySelector('.confirm-submit');
        submit.textContent = label;
        submit.classList.toggle('button-danger', danger);
        submit.classList.toggle('button-primary', !danger);

        element.querySelector('input[name="action"]').value = button.dataset.action;
        element.querySelector('input[name="campaignID"]').value = button.dataset.id;

        modal.show();
    });
});

// upload dialog on the campaign view, the photo picker is set up by components/galleryUploader.php
document.getElementById('gallery-upload-open')?.addEventListener('click', () => {
    const dialog = document.getElementById('gallery-dialog');
    dialog.querySelector('.gallery-uploader').galleryReset();
    dialog.showModal();
});
