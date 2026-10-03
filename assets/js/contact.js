// contact.js runs the phone formatting, thank you popup and map on contact.php

// phone number field
const phone = document.getElementById('phone');

// format the phone number as it is typed
phone.addEventListener('input', (event) => {
    // keep digits only
    let digits = event.target.value.replace(/\D/g, '');

    // change +27 to 0
    if (digits.startsWith('27')) {
        digits = '0' + digits.slice(2);
    }

    // no more than 10 digits
    digits = digits.slice(0, 10);

    // add spaces between the groups
    const parts = [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6, 10)].filter(Boolean);

    event.target.value = parts.join(' ');
});

// thank you popup
const successModal = document.getElementById('successModal');
const closeSuccessModal = document.getElementById('closeSuccessModal');
const okSuccessModal = document.getElementById('okSuccessModal');

// close the popup and let the page scroll again
function hideSuccessModal() {
    successModal.classList.remove('is-visible');
    successModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('contact-modal-open');
}

// when the popup loads open stop the page scrolling and focus it
if (successModal.classList.contains('is-visible')) {
    document.body.classList.add('contact-modal-open');
    okSuccessModal.focus();

    // clean up the address so refreshing does not show the message again
    history.replaceState(null, '', 'contact.php');
}

// close and ok buttons
closeSuccessModal.addEventListener('click', hideSuccessModal);
okSuccessModal.addEventListener('click', hideSuccessModal);

// clicking the dark overlay closes the popup
successModal.addEventListener('click', function (event) {
    if (event.target === successModal) {
        hideSuccessModal();
    }
});

// escape closes the popup
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && successModal.classList.contains('is-visible')) {
        hideSuccessModal();
    }
});

// load the google map only when the visitor asks for it so no google cookies are set before then
const contactMapLoad = document.getElementById('contactMapLoad');

contactMapLoad.addEventListener('click', function () {
    const map = document.createElement('iframe');
    map.src = 'https://www.google.com/maps?q=' + encodeURIComponent(contactMapLoad.dataset.address) + '&output=embed';
    map.title = 'Map showing the Likhwezi Technologies office in Fourways';
    map.loading = 'lazy';
    map.referrerPolicy = 'no-referrer-when-downgrade';
    map.allowFullscreen = true;

    document.getElementById('contactMapFrame').replaceChildren(map);
});
