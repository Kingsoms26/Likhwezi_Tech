// cym.js runs the registration form and popups on cym.php

// format the phone number as it is typed
const phone = document.getElementById('phone');
phone.addEventListener('input', e => {
    let d = e.target.value.replace(/\D/g, '');
    if (d.startsWith('27')) d = '0' + d.slice(2);
    d = d.slice(0, 10);
    const parts = [d.slice(0, 3), d.slice(3, 6), d.slice(6, 10)].filter(Boolean);
    e.target.value = parts.join(' ');
});

// registration popup
const registerButtons = document.querySelectorAll('.register-btn');
const modalOverlay = document.getElementById('modal-overlay');
const modalClose = document.getElementById('modal-close');
const successModal = document.getElementById('success-modal');
const successModalCloseButtons = document.querySelectorAll('.success-modal-close, .success-close-btn');
const registerForm = document.getElementById('register-form');
const emailInput = document.getElementById('reg-email');
const registerMessage = document.getElementById('register-message');
const ageInput = document.getElementById('register-age');
const firstConsent = document.querySelector('input[name="consent-main"]');
const mediaConsent = document.querySelector('input[name="media-consent"]');
const guardianConsent = document.querySelector('input[name="guardian-consent"]');

// paste as plain text on one line
function stripRichTextPaste(event) {
    const clipboard = event.clipboardData || window.clipboardData;
    if (!clipboard) return;

    const text = clipboard.getData('text/plain');
    if (!text) return;

    event.preventDefault();

    const input = event.target;
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    const nextValue = input.value.slice(0, start) + text + input.value.slice(end);

    input.value = nextValue.replace(/\s+/g, ' ').trim();
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"]').forEach((input) => {
    input.addEventListener('paste', stripRichTextPaste);
});

// email or phone is needed, both are marked until one is filled in
function updateSubmitState() {
    const hasEmail = emailInput.value.trim() !== '';
    const hasPhone = phone.value.trim() !== '';

    if (hasEmail || hasPhone) {
        emailInput.closest('.form-field').classList.remove('is-missing');
        phone.closest('.form-field').classList.remove('is-missing');
    }
}

// mark email and phone when both are empty
function flagMissingContact() {
    emailInput.closest('.form-field').classList.add('is-missing');
    phone.closest('.form-field').classList.add('is-missing');
    emailInput.focus();
}

// check the required fields and consent before sending
function validateRegistrationForm() {
    const requiredFields = [
        document.getElementById('register-fname'),
        document.getElementById('register-lname'),
        document.getElementById('register-age')
    ];

    for (const field of requiredFields) {
        if (!field.value.trim()) {
            field.focus();
            return false;
        }
    }

    if (!firstConsent.checked) {
        firstConsent.focus();
        return false;
    }

    if (Number(ageInput.value) < 18) {
        const guardianFieldsToCheck = [
            document.getElementById('guardian-fname'),
            document.getElementById('guardian-lname'),
            document.getElementById('register-guardRela'),
            document.getElementById('guardian-phone')
        ];

        for (const field of guardianFieldsToCheck) {
            if (!field.value.trim()) {
                field.focus();
                return false;
            }
        }

        if (!guardianConsent.checked) {
            guardianConsent.focus();
            return false;
        }
    }

    return true;
}

// open and close the popups
function openRegistrationModal(event) {
    event.preventDefault();
    modalOverlay.classList.add('active');
    document.body.classList.add('modal-open');
    document.getElementById('register-fname').focus();
}

function closeRegistrationModal() {
    modalOverlay.classList.remove('active');
    document.body.classList.remove('modal-open');
}

function openModal(modal) {
    modal.classList.add('active');
    document.body.classList.add('modal-open');
}

function closeModal(modal) {
    modal.classList.remove('active');
    const anyModalOpen = document.querySelector('.modal-overlay.active');
    if (!anyModalOpen) {
        document.body.classList.remove('modal-open');
    }
}

// register buttons, close button and clicking outside
registerButtons.forEach((button) => {
    button.addEventListener('click', openRegistrationModal);
});
modalClose.addEventListener('click', closeRegistrationModal);
modalOverlay.addEventListener('click', (event) => {
    if (event.target === modalOverlay) {
        closeRegistrationModal();
    }
});

// close the success popup
successModalCloseButtons.forEach((button) => {
    button.addEventListener('click', () => closeModal(successModal));
});

successModal.addEventListener('click', (event) => {
    if (event.target === successModal) {
        closeModal(successModal);
    }
});

// escape closes the popups
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeModal(successModal);
        closeRegistrationModal();
    }
});

// check the form then send it
registerForm.addEventListener('submit', (event) => {
    const hasEmail = emailInput.value.trim() !== '';
    const hasPhone = phone.value.trim() !== '';

    if (!validateRegistrationForm()) {
        event.preventDefault();
        return;
    }

    if (!hasEmail && !hasPhone) {
        event.preventDefault();
        flagMissingContact();
        return;
    }

    event.preventDefault();
    submitRegistration();
});

// send the registration, the success popup only shows once it has been saved
async function submitRegistration() {
    const submitButton = registerForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    clearServerErrors();

    let result;
    try {
        const response = await fetch(registerForm.action, {
            method: 'POST',
            body: new FormData(registerForm),
            headers: { 'Accept': 'application/json' }
        });
        result = await response.json();
    } catch (error) {
        result = { ok: false, message: 'We could not save your registration right now. Please try again, or email us at ' + registerForm.dataset.contactEmail + '.' };
    }
    submitButton.disabled = false;

    if (!result.ok) {
        showServerErrors(result);
        return;
    }

    closeRegistrationModal();
    openModal(successModal);
    registerForm.reset();
    updateGuardianFields();
    updateSubmitState();
}

// clear the error message and marked fields
function clearServerErrors() {
    registerMessage.hidden = true;
    registerMessage.textContent = '';
    registerForm.querySelectorAll('.form-field.is-missing').forEach((field) => field.classList.remove('is-missing'));
}

// show the errors from the server and mark the fields
function showServerErrors(result) {
    const errors = result.errors || {};
    const names = Object.keys(errors);

    registerMessage.textContent = names.length
        ? names.map((name) => errors[name]).join(' ')
        : result.message;
    registerMessage.hidden = false;

    let first = null;
    names.forEach((name) => {
        const input = registerForm.querySelector(`[name="${name}"]`);
        if (!input) return;
        const field = input.closest('.form-field');
        if (field) field.classList.add('is-missing');
        first = first || input;
    });
    (first || registerMessage).focus();
}

// unmark email and phone as they are filled in
emailInput.addEventListener('input', updateSubmitState);
phone.addEventListener('input', updateSubmitState);
updateSubmitState();

const guardianFields = document.getElementById('guardian-fields');
const guardianInputs = guardianFields.querySelectorAll('input:not([type="checkbox"])');
const guardianConsentInSection = guardianFields.querySelector('input[type="checkbox"]');

// show and require the guardian details only for under 18s
function updateGuardianFields() {
    const rawAge = ageInput.value.trim();
    const parsedAge = Number(rawAge);
    const showGuardianFields = rawAge !== '' && !Number.isNaN(parsedAge) && parsedAge < 18;

    guardianFields.hidden = !showGuardianFields;

    guardianInputs.forEach((input) => {
        input.required = showGuardianFields;
    });
    guardianConsentInSection.required = showGuardianFields;

    if (!showGuardianFields) {
        guardianConsentInSection.checked = false;
        guardianConsent.checked = false;
    }
}

ageInput.addEventListener('input', updateGuardianFields);
updateGuardianFields();
