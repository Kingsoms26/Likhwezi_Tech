// enquiryDialog.js runs the dialog in components/enquiryDialog.php

// fill in and open the enquiry details dialog
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('enquiry-dialog');
    const field = (name) => dialog.querySelector(`[data-field="${name}"]`);

    document.querySelectorAll('.enquiry-view').forEach((button) => {
        button.addEventListener('click', () => {
            const enquiry = JSON.parse(button.dataset.enquiry);

            Object.entries(enquiry).forEach(([name, value]) => {
                field(name).textContent = value;
            });

            // link the email and phone number, no phone link when there is no number
            field('email').href = 'mailto:' + enquiry.email;
            field('phoneNumber').toggleAttribute('href', enquiry.phoneNumber !== '—');
            if (enquiry.phoneNumber !== '—') field('phoneNumber').href = 'tel:' + enquiry.phoneNumber;

            dialog.showModal();
        });
    });

    // clicking outside the card closes the dialog
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
});
