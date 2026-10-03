// privacyModal.js runs the privacy notice popup in components/privacyModal.php

(function () {
    const privacyModal = document.getElementById('privacyModal');
    const firstCloseButton = privacyModal.querySelector('[data-privacy-modal-close]');

    // remember what opened the popup so focus can return to it
    let privacyModalTrigger = null;

    // open the popup
    function showPrivacyModal(triggerElement) {
        privacyModalTrigger = triggerElement;
        privacyModal.classList.add('is-visible');
        privacyModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('contact-modal-open');
        firstCloseButton.focus();
    }

    // find the consent checkbox nearest to the link that opened the popup
    function consentCheckboxFor(trigger) {
        for (let element = trigger.parentElement; element && element.tagName !== 'BODY'; element = element.parentElement) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) return checkbox;
            if (element.tagName === 'FORM') break;
        }
        return null;
    }

    // close the popup
    function hidePrivacyModal() {
        privacyModal.classList.remove('is-visible');
        privacyModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('contact-modal-open');

        if (privacyModalTrigger) {
            privacyModalTrigger.focus();
        }
    }

    // open the notice from any link on the page, including ones inside checkbox labels
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-privacy-modal-open]');
        if (trigger) {
            event.preventDefault();
            showPrivacyModal(trigger);
        }
    });

    // close buttons
    privacyModal.querySelectorAll('[data-privacy-modal-close]').forEach(function (button) {
        button.addEventListener('click', hidePrivacyModal);
    });

    // i understand ticks the consent box next to the link that opened the notice
    privacyModal.querySelector('[data-privacy-modal-accept]').addEventListener('click', function () {
        const checkbox = privacyModalTrigger && consentCheckboxFor(privacyModalTrigger);
        if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
        hidePrivacyModal();
    });

    // clicking the dark overlay closes the popup
    privacyModal.addEventListener('click', function (event) {
        if (event.target === privacyModal) {
            hidePrivacyModal();
        }
    });

    // escape closes only this popup and leaves any form popup underneath open
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && privacyModal.classList.contains('is-visible')) {
            event.stopImmediatePropagation();
            hidePrivacyModal();
        }
    });
})();
