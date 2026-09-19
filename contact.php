<?php
session_start();
include 'tools/dbConnection.php';
$pageTitle = 'Contact Us';

// Normalises South African mobile numbers before they are stored or validated.
function normalise_phone(string $input): ?string {
    // Removes spaces, brackets, hyphens, and any other non-digit characters.
    $digits = preg_replace('/\D/', '', $input);

    // Converts the international +27 format to the local 0 format.
    if (str_starts_with($digits, '27')) {
        $digits = '0' . substr($digits, 2);
    }

    // Converts the international 0027 format to the local 0 format.
    if (str_starts_with($digits, '0027')) {
        $digits = '0' . substr($digits, 4);
    }

    // Accepts only a South African number beginning with 0 and containing 10 digits.
    return preg_match('/^0[1-8]\d{8}$/', $digits) ? $digits : null;
}
?>

<!DOCTYPE html>
<html lang="en">

    <?php include 'components/header.php';?>

    <body>

    <?php include 'components/navBar.php';?>

        <main class="contact-page">

            <section class="contact-hero page-hero">
                <div class="container">

                    <h1 class="page-hero-title fw-bold">
                        Contact Us
                    </h1>

                    <p class="page-hero-description">
                        Get in touch with Likhwezi Technologies to discuss your
                        consulting needs and discover how we can assist your organisation.
                    </p>

                </div>
            </section>

            <hr class="contact-divider">

            <section class="contact-form-section py-5">
                <div class="container">

                    <!-- Keeps the form centred and readable on larger screens. -->
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-8">

                            <!-- Form heading. -->
                            <h2 class="h3 mb-4">
                                Send us an enquiry
                            </h2>

                            <!--
                                The form currently submits back to contact.php.
                                Server-side validation and database insertion
                                will be added to the submission workflow.
                            -->
                            <form
                                method="post"
                                action="contact.php"
                            >

                                <!-- Full name field. -->
                                <div class="mb-4">
                                    <label
                                        for="name"
                                        class="form-label"
                                    >
                                        Full name
                                        <span aria-hidden="true">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        placeholder="Enter your full name"
                                        maxlength="150"
                                        required
                                    >
                                </div>

                                <!-- Phone number field placed directly beneath the full name field. -->
                                <div class="mb-4">
                                    <label
                                        for="phone"
                                        class="form-label"
                                    >
                                        Phone number
                                        <span aria-hidden="true">*</span>
                                    </label>

                                    <input
                                        type="tel"
                                        class="form-control"
                                        id="phone"
                                        name="phone"
                                        placeholder="083 401 0080"
                                        maxlength="12"
                                        inputmode="numeric"
                                        required
                                    >
                                </div>

                                <!-- Email field. -->
                                <div class="mb-4">
                                    <label
                                        for="email"
                                        class="form-label"
                                    >
                                        Email address
                                        <span aria-hidden="true">*</span>
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        placeholder="Enter your email address"
                                        maxlength="255"
                                        required
                                    >
                                </div>

                                <!-- Organisation field. -->
                                <div class="mb-4">
                                    <label
                                        for="organisation"
                                        class="form-label"
                                    >
                                        Organisation
                                        <span aria-hidden="true">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="organisation"
                                        name="organisation"
                                        placeholder="Enter your organisation"
                                        maxlength="255"
                                        required
                                    >
                                </div>

                                <!-- Meeting type field. -->
                                <div class="mb-4">
                                    <label
                                        for="meetingType"
                                        class="form-label"
                                    >
                                        Preferred meeting type
                                        <span aria-hidden="true">*</span>
                                    </label>

                                    <select
                                        class="form-select"
                                        id="meetingType"
                                        name="meetingType"
                                        required
                                    >

                                        <!-- Prevents submission without a selection. -->
                                        <option
                                            value=""
                                            selected
                                            disabled
                                        >
                                            Select a meeting type
                                        </option>

                                        <!-- Database-compatible virtual meeting value. -->
                                        <option value="virtual">
                                            Virtual meeting
                                        </option>

                                        <!-- Database-compatible physical meeting value. -->
                                        <option value="physical">
                                            Face-to-face meeting
                                        </option>

                                    </select>
                                </div>

                                <!-- Enquiry description field. -->
                                <div class="mb-4">
                                    <label
                                        for="description"
                                        class="form-label"
                                    >
                                        How can we help?
                                        <span aria-hidden="true">*</span>
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="description"
                                        name="description"
                                        rows="6"
                                        placeholder="Describe your enquiry"
                                        maxlength="5000"
                                        required
                                    ></textarea>
                                </div>

                                <!-- Explains that every field is mandatory. -->
                                <p class="small text-muted">
                                    All fields are required.
                                </p>

                                <!-- Mandatory privacy consent checkbox. -->
                                <div class="form-check contact-consent mb-4">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="privacyConsent"
                                        name="privacyConsent"
                                        value="1"
                                        required
                                    >

                                    <div class="consent-copy">
                                        <label class="form-check-label" for="privacyConsent">
                                            I have read and understood the
                                        </label>

                                        <!-- Opens the full privacy statement without toggling the checkbox. -->
                                        <button type="button" class="privacy-link" id="openPrivacyModal">
                                            Privacy Notice and Consent
                                        </button>

                                        <span>to Likhwezi Technologies collecting and processing my personal information in accordance with POPIA.</span>
                                    </div>
                                </div>

                                <!-- Submits the enquiry form. -->
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Submit enquiry
                                </button>

                            </form>

                        </div>
                    </div>

                </div>
            </section>

            <!-- Privacy Notice and Consent modal. -->
            <div
                class="contact-modal-overlay"
                id="privacyModal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="privacyModalTitle"
                aria-hidden="true"
            >
                <div class="contact-modal-box privacy-modal-box">
                    <!-- Allows the visitor to close the privacy notice. -->
                    <button
                        type="button"
                        class="contact-modal-close"
                        id="closePrivacyModal"
                        aria-label="Close privacy notice"
                    >&times;</button>

                    <!-- Privacy notice heading. -->
                    <h2 id="privacyModalTitle">Privacy Notice and Consent</h2>

                    <!-- Privacy wording adapted from CyberYoungMinds.php. -->
                    <div class="privacy-modal-content">
                        <p><strong>Consent to Collection and Processing of Personal Information</strong></p>
                        <p>
                            By submitting an enquiry or engaging with Likhwezi Technologies (Pty) Ltd,
                            you consent to the collection, storage, processing, and use of your personal
                            information for lawful business purposes.
                        </p>
                        <p>
                            Likhwezi Technologies may collect information such as your name, contact
                            details, organisation information, and other details necessary to provide
                            services, support, and communications.
                        </p>
                        <p>
                            We undertake to process your personal information in accordance with the
                            Protection of Personal Information Act, 2013 (POPIA), and other applicable
                            laws. Your information will only be used for the purpose for which it was
                            collected and may be shared with authorised service providers where necessary
                            to deliver services or comply with legal obligations.
                        </p>
                        <p>
                            You have the right to access, update, correct, or request the deletion of your
                            personal information, subject to applicable legal and contractual requirements.
                        </p>
                        <p>
                            If you do not agree to this consent, please refrain from submitting your
                            personal information through this website.
                        </p>
                    </div>

                    <!-- Confirms that the visitor has reviewed the notice. -->
                    <div class="contact-modal-actions">
                        <button type="button" class="btn btn-primary" id="understandPrivacyModal">
                            I understand
                        </button>
                    </div>
                </div>
            </div>

            <!--divider between sections. -->
            <hr class="contact-divider">

            <!-- Connect With Us section. -->
            <section class="connect-section py-5">
                <div class="container text-center">

                    <!-- Social media section heading. -->
                    <h2 class="h3 mb-3">
                        Connect with us
                    </h2>

                    <!-- Social media section introduction. -->
                    <p class="mb-4">
                        Follow Likhwezi Technologies on social media for updates,
                        insights, and company news.
                    </p>

                    <!--
                        Social media links.
                    -->
                    <div class="social-links d-flex justify-content-center gap-3">

                        <!-- Instagram -->
                        <a
                            href="https://www.instagram.com/likhwezitechnologies/"
                            class="social-icon-link instagram"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Visit Likhwezi Technologies on Instagram"
                            title="Instagram"
                        >
                            <i
                                class="bi bi-instagram"
                                aria-hidden="true"
                            ></i>
                        </a>

                        <!-- Facebook -->
                        <a
                            href="https://www.facebook.com/your_likhwezi_page/"
                            class="social-icon-link facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Visit Likhwezi Technologies on Facebook"
                            title="Facebook"
                        >
                            <i
                                class="bi bi-facebook"
                                aria-hidden="true"
                            ></i>
                        </a>

                        <!-- LinkedIn -->
                        <a
                            href="https://www.linkedin.com/company/your-likhwezi-company/"
                            class="social-icon-link linkedin"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Visit Likhwezi Technologies on LinkedIn"
                            title="LinkedIn"
                        >
                            <i
                                class="bi bi-linkedin"
                                aria-hidden="true"
                            ></i>
                        </a>

                        <!-- YouTube -->
                        <a
                            href="https://www.youtube.com/@your_likhwezi_channel"
                            class="social-icon-link youtube"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Visit Likhwezi Technologies on YouTube"
                            title="YouTube"
                        >
                            <i
                                class="bi bi-youtube"
                                aria-hidden="true"
                            ></i>
                        </a>

                        <!-- X / Twitter -->
                        <a
                            href="https://x.com/your_likhwezi_account"
                            class="social-icon-link x-twitter"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Visit Likhwezi Technologies on X"
                            title="X"
                        >
                            <i
                                class="bi bi-twitter-x"
                                aria-hidden="true"
                            ></i>
                        </a>

                        <!-- TikTok -->
                        <a
                            href="https://www.tiktok.com/@your_likhwezi_account"
                            class="social-icon-link tiktok"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Visit Likhwezi Technologies on TikTok"
                            title="TikTok"
                        >
                            <i
                                class="bi bi-tiktok"
                                aria-hidden="true"
                            ></i>
                        </a>

                    </div>

                </div>
            </section>

        </main>

        <?php
        // Loads the shared footer and Bootstrap JavaScript bundle.
        include 'components/footer.php';
        ?>

        <script>
            // Gets the phone number input used by the enquiry form.
            const phone = document.getElementById('phone');

            // Formats the phone number as the visitor types it.
            phone.addEventListener('input', (event) => {
                // Keeps digits only so pasted or typed non-numeric characters are removed.
                let digits = event.target.value.replace(/\D/g, '');

                // Converts the international +27 format to the local 0 format.
                if (digits.startsWith('27')) {
                    digits = '0' + digits.slice(2);
                }

                // Limits the number to the 10 digits used by the local format.
                digits = digits.slice(0, 10);

                // Adds spaces after the area/mobile prefix and each following group.
                const parts = [
                    digits.slice(0, 3),
                    digits.slice(3, 6),
                    digits.slice(6, 10)
                ].filter(Boolean);

                event.target.value = parts.join(' ');
            });

            // Gets the privacy modal and its interactive controls.
            const privacyModal = document.getElementById('privacyModal');
            const openPrivacyModal = document.getElementById('openPrivacyModal');
            const closePrivacyModal = document.getElementById('closePrivacyModal');
            const understandPrivacyModal = document.getElementById('understandPrivacyModal');

            // Stores the element that opened the modal for keyboard accessibility.
            let privacyModalTrigger = null;

            // Opens the privacy notice and prevents background page scrolling.
            function showPrivacyModal(triggerElement) {
                privacyModalTrigger = triggerElement;
                privacyModal.classList.add('is-visible');
                privacyModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('contact-modal-open');
                closePrivacyModal.focus();
            }

            // Closes the privacy notice and returns focus to the opening link.
            function hidePrivacyModal() {
                privacyModal.classList.remove('is-visible');
                privacyModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('contact-modal-open');

                if (privacyModalTrigger) {
                    privacyModalTrigger.focus();
                }
            }

            // Opens the modal when the privacy notice link is clicked.
            openPrivacyModal.addEventListener('click', function () {
                showPrivacyModal(openPrivacyModal);
            });

            // Closes the modal from the close or confirmation button.
            closePrivacyModal.addEventListener('click', hidePrivacyModal);
            understandPrivacyModal.addEventListener('click', hidePrivacyModal);

            // Closes the modal when the dark overlay is clicked.
            privacyModal.addEventListener('click', function (event) {
                if (event.target === privacyModal) {
                    hidePrivacyModal();
                }
            });

            // Allows keyboard users to close the modal with Escape.
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && privacyModal.classList.contains('is-visible')) {
                    hidePrivacyModal();
                }
            });
        </script>

    </body>
</html>
