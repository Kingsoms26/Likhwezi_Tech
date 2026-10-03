// campaign.js runs the campaign details and donation popups on campaign.php

// the popups and donation form
const campaignModalElement = document.getElementById('campaignModal');
const donationModalElement = document.getElementById('donationModal');
const donationForm = donationModalElement.querySelector('form');
const amountChoices = donationModalElement.querySelector('.donation-amounts');
const campaignModal = new bootstrap.Modal(campaignModalElement);
const donationModal = new bootstrap.Modal(donationModalElement);
const campaignIdInput = document.getElementById('campaignID');
const customAmountGroup = document.getElementById('customAmountGroup');
const customAmountInput = document.getElementById('customAmount');

// every campaign card by id so the donation form can be filled for any of them
const campaignCards = {};
document.querySelectorAll('.campaign-card').forEach(function(card) {
    campaignCards[card.dataset.campaignId] = card;
});

// amount raised of the goal, with the raised amount in bold like the cards
function fillStats(element, card) {
    const raised = document.createElement('strong');
    raised.textContent = card.dataset.raised;
    element.replaceChildren(raised, ' raised of ' + card.dataset.goal);
}

// fill a progress bar
function fillProgress(progress, percent) {
    progress.querySelector('.progress-bar').style.width = percent + '%';
    progress.setAttribute('aria-valuenow', percent);
}

// fill the details and donation popups with one campaign
function selectCampaign(card) {
    const campaignName = card.dataset.name || 'Campaign Details';
    const image = card.dataset.image || 'assets/images/placeholder.webp';
    const percent = card.dataset.percent || 0;
    const isFunded = card.classList.contains('is-funded');

    document.getElementById('campaignModalTitle').textContent = campaignName;
    document.getElementById('donationModalTitle').textContent = campaignName;
    document.getElementById('campaignModalImage').src = image;
    document.getElementById('campaignModalImage').alt = campaignName;
    document.getElementById('campaignModalDescription').textContent = card.dataset.description || 'More information about the selected campaign will be displayed here.';
    document.getElementById('donationSummaryImage').src = image;
    fillStats(document.getElementById('campaignModalStats'), card);
    fillStats(document.getElementById('donationSummaryStats'), card);
    fillProgress(document.getElementById('campaignModalProgress'), percent);
    fillProgress(document.getElementById('donationSummaryProgress'), percent);

    campaignModalElement.classList.toggle('is-funded', isFunded);
    donationModalElement.classList.toggle('is-funded', isFunded);
    campaignIdInput.value = card.dataset.campaignId || '';
}

// show one popup after another has finished closing so they never overlap
function switchModal(fromElement, fromModal, toModal) {
    fromElement.addEventListener('hidden.bs.modal', function() {
        toModal.show();
    }, { once: true });
    fromModal.hide();
}

// clicking a card opens its details
Object.values(campaignCards).forEach(function(card) {
    card.addEventListener('click', function() {
        selectCampaign(card);
        campaignModal.show();
    });
});

// make your donation opens the donation form
document.getElementById('openDonationModal').addEventListener('click', function() {
    if (!campaignIdInput.value) {
        alert('Please choose a campaign first.');
        return;
    }

    switchModal(campaignModalElement, campaignModal, donationModal);
});

// show the custom amount box only when custom is chosen
document.querySelectorAll('input[name="presetAmount"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const isCustom = this.value === 'custom';
        customAmountInput.disabled = !isCustom;
        customAmountGroup.classList.toggle('d-none', !isCustom);
        amountChoices.classList.remove('is-invalid');
        if (isCustom) {
            customAmountInput.focus();
        } else {
            customAmountInput.value = '';
        }
    });
});

// check the amount and required fields before going to PayFast
donationForm.addEventListener('submit', function(event) {
    const chosen = donationForm.querySelector('input[name="presetAmount"]:checked');
    const amountMissing = !chosen || (chosen.value === 'custom' && !customAmountInput.checkValidity());

    amountChoices.classList.toggle('is-invalid', amountMissing);
    if (amountMissing || !donationForm.checkValidity()) {
        event.preventDefault();
        donationForm.classList.add('was-validated');
        (amountMissing ? amountChoices.querySelector('.donation-amount') : donationForm.querySelector(':invalid')).focus();
        return;
    }

    // stop a double click from creating two donations
    const submitButton = donationForm.querySelector('[name="donation_submit"]');
    submitButton.disabled = true;
    donationForm.insertAdjacentHTML('beforeend', '<input type="hidden" name="donation_submit" value="1">');
});

// reopen the donation form when it had a problem
if (donationModalElement.dataset.reopen) {
    if (campaignCards[campaignIdInput.value]) {
        selectCampaign(campaignCards[campaignIdInput.value]);
    }
    donationModal.show();
}

// show the donation result
const resultModalElement = document.getElementById('donationResultModal');
if (resultModalElement) {
    const resultModal = new bootstrap.Modal(resultModalElement);
    resultModal.show();

    // clean up the address so refreshing does not show the message again
    history.replaceState(null, '', location.pathname);

    // try again reopens the donation form
    const retryButton = document.getElementById('retryDonation');
    if (retryButton) {
        retryButton.addEventListener('click', function() {
            selectCampaign(campaignCards[retryButton.dataset.campaignId]);
            switchModal(resultModalElement, resultModal, donationModal);
        });
    }
}
