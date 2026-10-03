// registrations.js runs the results and popups on registrations.php

// registrations page script
document.addEventListener('DOMContentLoaded', function () {

    // 1 update the results without reloading the page

    const liveRegion = document.getElementById('results-live');
    const confirmForm = document.querySelector('#registration-confirm form');
    let activeRequest = null;

    // the results area on the page
    function resultsArea() {
        return document.getElementById('registrations-results');
    }

    // fetch new results and swap them in
    async function loadResults(url, options) {
        const settings = Object.assign({ addToHistory: true, focusKey: null }, options);
        const area = resultsArea();

        // a newer click cancels a request still in progress
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();

        area.classList.add('is-loading');
        area.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: { 'X-Registrations-Partial': '1' },
                credentials: 'same-origin',
                signal: activeRequest.signal
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);

            const template = document.createElement('template');
            template.innerHTML = (await response.text()).trim();
            const freshArea = template.content.querySelector('#registrations-results');

            if (!freshArea) throw new Error('Unexpected response');

            area.replaceWith(freshArea);

            // the server sends back the tidy address for what is now shown
            const shownUrl = freshArea.dataset.url;
            if (settings.addToHistory) {
                history.pushState({ registrations: true }, '', shownUrl);
            }

            // archive and restore should come back to the view now on screen
            confirmForm.action = shownUrl;

            // put keyboard focus back on the control that was used
            const focusTarget = settings.focusKey
                && freshArea.querySelector('[data-focus-key="' + settings.focusKey + '"]');
            if (focusTarget) focusTarget.focus({ preventScroll: true });

            // after using the page links at the bottom bring the top of the table into view
            if (settings.focusKey && settings.focusKey.indexOf('page-') === 0
                && freshArea.getBoundingClientRect().top < 0) {
                freshArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            liveRegion.textContent = freshArea.querySelector('#results-announcement').textContent;
        } catch (error) {
            if (error.name === 'AbortError') return;
            window.location.href = url;   // fall back to a normal page load
        }
    }

    // sort headings, active and archived tabs, page links and clear
    document.addEventListener('click', function (event) {
        const link = event.target.closest(
            '#registrations-results a.js-results-link, #registrations-results .js-results-links a'
        );
        if (!link) return;

        // ctrl, cmd, shift and middle click still open a new tab
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;

        event.preventDefault();
        loadResults(link.href, { focusKey: link.dataset.focusKey });
    });

    // filter form, empty fields are left out to keep the address short
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('.js-results-form');
        if (!form) return;

        event.preventDefault();

        const params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            if (value !== '') params.append(key, value);
        });

        loadResults(form.action.split('?')[0] + '?' + params.toString(), { focusKey: 'filter-apply' });
    });

    // back and forward buttons show the results for that address
    window.addEventListener('popstate', function () {
        loadResults(window.location.href, { addToHistory: false });
    });

    // 2 popups

    if (!window.bootstrap) {
        console.error('Bootstrap JS did not load: registration modals are unavailable.');
        return;
    }

    const detailElement = document.getElementById('registration-detail');
    const confirmElement = document.getElementById('registration-confirm');
    const detailModal = bootstrap.Modal.getOrCreateInstance(detailElement);
    const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmElement);

    // shown when an optional value is missing
    const notProvided = 'Not provided';

    // read fresh each time since the details are replaced with the results
    function currentRegistrations() {
        return JSON.parse(document.getElementById('registration-data').textContent);
    }

    // fill in one field in the view popup
    function setField(name, value) {
        const field = detailElement.querySelector('[data-field="' + name + '"]');
        field.textContent = value || notProvided;
        field.classList.toggle('is-empty', !value);
    }

    // fill in the view popup and open it
    function openDetails(id) {
        const r = currentRegistrations()[id];
        if (!r) return;

        setField('name', r.name);
        setField('ageText', r.age + ' at registration');
        setField('programme', r.programme);
        setField('registeredAt', r.registeredAt);
        setField('id', '#' + r.id);
        setField('email', r.email);
        setField('phone', r.phone);
        setField('privacyText', 'Given ' + r.consentGivenAt);
        setField('mediaText', r.mediaConsent ? 'Given' : 'Not given');

        // guardian details only apply to under 18s
        detailElement.querySelector('[data-section="guardian"]').hidden = !r.isMinor;
        if (r.isMinor) {
            setField('guardianName', r.guardianName);
            setField('guardianRelationship', r.guardianRelationship);
            setField('guardianPhone', r.guardianPhone);
            setField('guardianEmail', r.guardianEmail);
            setField('guardianConsentText', r.guardianConsentGivenAt ? 'Given ' + r.guardianConsentGivenAt : '');
        }

        // archive details only for archived registrations
        detailElement.querySelector('[data-section="archive"]').hidden = !r.isArchived;
        if (r.isArchived) {
            setField('archivedAt', r.archivedAt);
            setField('archivedBy', r.archivedBy);
        }

        detailModal.show();
    }

    // fill in the archive or restore confirmation and open it
    function openConfirm(action, id, name) {
        const isArchive = action === 'archive';

        confirmElement.querySelector('#confirm-title').textContent =
            isArchive ? 'Archive this registration?' : 'Restore this registration?';

        confirmElement.querySelector('.confirm-message').textContent = isArchive
            ? name + ' will move to Archived and no longer count towards the totals. An Administrator can restore it later.'
            : name + ' will move back to Active registrations.';

        const submit = confirmElement.querySelector('.confirm-submit');
        submit.textContent = isArchive ? 'Archive' : 'Restore';
        submit.classList.toggle('button-danger', isArchive);
        submit.classList.toggle('button-primary', !isArchive);

        confirmElement.querySelector('input[name="action"]').value = action;
        confirmElement.querySelector('input[name="registrationID"]').value = id;

        confirmModal.show();
    }

    // view, archive and restore buttons
    document.addEventListener('click', function (event) {
        const viewButton = event.target.closest('.js-view-registration');
        if (viewButton) {
            openDetails(viewButton.dataset.id);
            return;
        }

        const actionButton = event.target.closest('.js-confirm-action');
        if (actionButton) {
            openConfirm(actionButton.dataset.action, actionButton.dataset.id, actionButton.dataset.name);
        }
    });
});
