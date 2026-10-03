<?php
    // contact.php is the contact us page
    // contains the contact details, the office map, social links and the enquiry form
    require_once __DIR__ . '/includes/security.php';

    // only connect to the database when an enquiry is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include 'config/dbConnection.php';
    }
    $pageTitle = 'Contact Us';

    // fetch the phone, email and address, admins edit them on staff/manageContent.php
    require_once __DIR__ . '/includes/helpers/siteContent.php';
    $siteContact = siteSettings();

    // turn a phone number into 10 digits starting with 0, or null if it is not valid
    function normalise_phone(string $input): ?string {
        // keep digits only
        $digits = preg_replace('/\D/', '', $input);

        // change +27 to 0
        if (str_starts_with($digits, '27')) {
            $digits = '0' . substr($digits, 2);
        }

        // change 0027 to 0
        if (str_starts_with($digits, '0027')) {
            $digits = '0' . substr($digits, 4);
        }

        // only accept a south african number starting with 0 with 10 digits
        return preg_match('/^0[1-8]\d{8}$/', $digits) ? $digits : null;
    }

    // save an enquiry, the archivable entity is saved first then the enquiry with the same id
    function save_enquiry(mysqli $conn, array $enquiry): bool {
        $conn->begin_transaction();

        if (!$conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Enquiry')")) {
            $conn->rollback();
            return false;
        }

        $enquiryID = $conn->insert_id;

        $stmt = $conn->prepare(
            "INSERT INTO Enquiry (enquiryID, name, companyName, email, phoneNumber, description, meetingType)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            $conn->rollback();
            return false;
        }

        $stmt->bind_param(
            "issssss",
            $enquiryID,
            $enquiry['name'],
            $enquiry['organisation'],
            $enquiry['email'],
            $enquiry['phone'],
            $enquiry['description'],
            $enquiry['meetingType']
        );

        if (!$stmt->execute()) {
            $conn->rollback();
            return false;
        }

        return $conn->commit();
    }

    // visitors must wait this many seconds between enquiries
    const ENQUIRY_COOLDOWN_SECONDS = 30;

    // token that ties each submission to a form this site served
    if (empty($_SESSION['enquiryToken'])) {
        $_SESSION['enquiryToken'] = bin2hex(random_bytes(32));
    }

    // what was typed, shown again in the form when something is wrong
    $values = [
        'name' => '',
        'phone' => '',
        'email' => '',
        'organisation' => '',
        'meetingType' => '',
        'description' => '',
        'privacyConsent' => false,
    ];
    $errors = [];
    $formError = '';

    // links from other pages can fill in the description with a known request like the consulting package
    // only these are accepted so the url cannot put any text in the form
    $enquiryPresets = [
        'consulting-package' => "I would like to request the Consulting Package.\n\nPlease contact me to discuss how it could support my organisation, including research and analytics, strategic recommendations, solution design and implementation, and testing and evaluation.",
    ];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($enquiryPresets[$_GET['enquiry'] ?? ''])) {
        $values['description'] = $enquiryPresets[$_GET['enquiry']];
    }

    // handle the enquiry form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $values = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'organisation' => trim($_POST['organisation'] ?? ''),
            'meetingType' => $_POST['meetingType'] ?? '',
            'description' => trim($_POST['description'] ?? ''),
            'privacyConsent' => ($_POST['privacyConsent'] ?? '') === '1',
        ];

        // bots fill in the hidden website field, pretend it worked so they move on
        if (trim($_POST['website'] ?? '') !== '') {
            header('Location: contact.php?submitted=1');
            exit;
        }

        // check the token and the wait between enquiries
        if (!hash_equals($_SESSION['enquiryToken'], $_POST['enquiryToken'] ?? '')) {
            $formError = 'Your session expired. Please submit the form again.';
        } elseif (time() - ($_SESSION['lastEnquiryAt'] ?? 0) < ENQUIRY_COOLDOWN_SECONDS) {
            $formError = 'You have just sent an enquiry. Please wait a moment before sending another.';
        }

        // check each field
        if ($values['name'] === '') {
            $errors['name'] = 'Please enter your full name.';
        } elseif (mb_strlen($values['name']) > 150) {
            $errors['name'] = 'Your name must be 150 characters or fewer.';
        }

        $normalisedPhone = normalise_phone($values['phone']);
        if ($values['phone'] === '') {
            $errors['phone'] = 'Please enter your phone number.';
        } elseif ($normalisedPhone === null) {
            $errors['phone'] = 'Please enter a valid South African phone number, e.g. 082 123 4567.';
        }

        if ($values['email'] === '') {
            $errors['email'] = 'Please enter your email address.';
        } elseif (mb_strlen($values['email']) > 255 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (mb_strlen($values['organisation']) > 255) {
            $errors['organisation'] = 'Your organisation name must be 255 characters or fewer.';
        }

        if (!in_array($values['meetingType'], ['virtual', 'physical'], true)) {
            $errors['meetingType'] = 'Please choose a meeting type.';
        }

        if ($values['description'] === '') {
            $errors['description'] = 'Please tell us how we can help.';
        } elseif (mb_strlen($values['description']) > 5000) {
            $errors['description'] = 'Your enquiry must be 5000 characters or fewer.';
        }

        if (!$values['privacyConsent']) {
            $errors['privacyConsent'] = 'Please confirm that you have read the Privacy Notice and Consent.';
        }

        // save the enquiry
        if ($formError === '' && empty($errors)) {
            $saved = isset($conn) && !$conn->connect_error && save_enquiry($conn, [
                'name' => $values['name'],
                'phone' => $normalisedPhone,
                'email' => $values['email'],
                'organisation' => $values['organisation'] !== '' ? $values['organisation'] : null,
                'meetingType' => $values['meetingType'],
                'description' => $values['description'],
            ]);

            if ($saved) {
                $_SESSION['lastEnquiryAt'] = time();
                $_SESSION['enquiryToken'] = bin2hex(random_bytes(32));

                // go back to the page so refreshing does not send the form again
                header('Location: contact.php?submitted=1');
                exit;
            }

            error_log('Contact form: could not save enquiry' . (isset($conn) ? ': ' . $conn->error : ''));
            $formError = 'We could not send your enquiry right now. Please try again, or email us at ' . $siteContact['contactEmail'] . '.';
        }
    }

    // show the thank you popup after a successful submission
    $enquirySubmitted = isset($_GET['submitted']);

    // error style and message for a field that failed
    function field_class(array $errors, string $field): string {
        return isset($errors[$field]) ? ' is-invalid' : '';
    }

    function field_error(array $errors, string $field): string {
        return isset($errors[$field])
            ? '<div class="invalid-feedback d-block" id="' . $field . 'Error">' . htmlspecialchars($errors[$field]) . '</div>'
            : '';
    }

    // link a field to its error message for screen readers
    function field_describedby(array $errors, string $field): string {
        return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $field . 'Error"' : '';
    }
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <main class="contact-page">

            <!-- hero section -->
            <section class="hero hero-text-only">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <h1>Contact Us</h1>
                        <p>Get in touch with Likhwezi Technologies to discuss your consulting needs and discover how we can assist your organisation.</p>
                    </div>

                    <!-- quick ways to get in touch, the phone and email buttons open the visitor's phone or mail app -->
                    <div class="contact-quick-actions p-1">
                        <a href="#enquiry-form" class="btn btn-primary">
                            <i class="bi bi-calendar-check" aria-hidden="true"></i> Book a consultation
                        </a>
                        <a href="<?= htmlspecialchars(phoneHref($siteContact['contactPhone'])) ?>" class="btn btn-ghost">
                            <i class="bi bi-telephone" aria-hidden="true"></i> <?= htmlspecialchars($siteContact['contactPhone']) ?>
                        </a>
                        <a href="mailto:<?= htmlspecialchars($siteContact['contactEmail']) ?>" class="btn btn-ghost">
                            <i class="bi bi-envelope" aria-hidden="true"></i> <?= htmlspecialchars($siteContact['contactEmail']) ?>
                        </a>
                    </div>
                </div>
            </section>

            <hr>

            <!-- contact details and enquiry form -->
            <section class="page-section contact-section">

                <!-- details beside the form on wide screens and above it on small screens -->
                <div class="contact-layout">

                    <!-- contact details, the same as the footer -->
                    <aside class="contact-details" aria-labelledby="contactDetailsTitle">
                        <h2 class="section-title" id="contactDetailsTitle">Get in touch</h2>

                        <ul class="contact-detail-list">
                            <li class="contact-detail">
                                <span class="contact-detail-icon" aria-hidden="true"><i class="bi bi-telephone"></i></span>
                                <div>
                                    <p class="contact-detail-label">Call us</p>
                                    <a class="contact-detail-value" href="<?= htmlspecialchars(phoneHref($siteContact['contactPhone'])) ?>"><?= htmlspecialchars($siteContact['contactPhone']) ?></a>
                                </div>
                            </li>
                            <li class="contact-detail">
                                <span class="contact-detail-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                                <div>
                                    <p class="contact-detail-label">Email us</p>
                                    <a class="contact-detail-value" href="mailto:<?= htmlspecialchars($siteContact['contactEmail']) ?>"><?= htmlspecialchars($siteContact['contactEmail']) ?></a>
                                    <p class="contact-detail-note">We reply within 48 hours.</p>
                                </div>
                            </li>
                            <li class="contact-detail">
                                <span class="contact-detail-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                                <div>
                                    <p class="contact-detail-label">Visit us</p>
                                    <p class="contact-detail-value"><?= htmlspecialchars($siteContact['contactAddress']) ?></p>
                                    <p class="contact-detail-note">Face-to-face meetings by appointment.</p>
                                </div>
                            </li>
                        </ul>

                        <!-- office map, google maps sets its own cookies so it only loads when the visitor asks for it -->
                        <div class="contact-map">
                            <div class="contact-map-frame" id="contactMapFrame">
                                <button type="button" class="contact-map-load" id="contactMapLoad" data-address="<?= htmlspecialchars($siteContact['contactAddress']) ?>">
                                    <i class="bi bi-map" aria-hidden="true"></i>
                                    <span>Show map</span>
                                    <small>Loads Google Maps</small>
                                </button>
                            </div>
                            <a class="contact-map-link" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= htmlspecialchars(urlencode($siteContact['contactAddress'])) ?>" target="_blank" rel="noopener noreferrer">Get directions</a>
                        </div>

                        <!-- social media links -->
                        <div class="contact-social">
                            <h3 class="contact-detail-label">Connect with us</h3>
                            <p class="contact-detail-note">Follow Likhwezi Technologies on social media for updates, insights, and company news.</p>

                            <div class="social-links d-flex gap-3">
                                <!-- instagram -->
                                <a href="https://www.instagram.com/likhwezitechnologies/" class="social-icon-link instagram" target="_blank" rel="noopener noreferrer" aria-label="Visit Likhwezi Technologies on Instagram" title="Instagram">
                                    <i class="bi bi-instagram" aria-hidden="true"></i>
                                </a>

                                <!-- hidden until the real account addresses are known, these are placeholders
                                     replace each link with the real one and move it above the php block below to show it -->
                                <?php /*
                                <!-- facebook -->
                                <a href="https://www.facebook.com/your_likhwezi_page/" class="social-icon-link facebook" target="_blank" rel="noopener noreferrer" aria-label="Visit Likhwezi Technologies on Facebook" title="Facebook">
                                    <i class="bi bi-facebook" aria-hidden="true"></i>
                                </a>

                                <!-- linkedin -->
                                <a href="https://www.linkedin.com/company/your-likhwezi-company/" class="social-icon-link linkedin" target="_blank" rel="noopener noreferrer" aria-label="Visit Likhwezi Technologies on LinkedIn" title="LinkedIn">
                                    <i class="bi bi-linkedin" aria-hidden="true"></i>
                                </a>

                                <!-- youtube -->
                                <a href="https://www.youtube.com/@your_likhwezi_channel" class="social-icon-link youtube" target="_blank" rel="noopener noreferrer" aria-label="Visit Likhwezi Technologies on YouTube" title="YouTube">
                                    <i class="bi bi-youtube" aria-hidden="true"></i>
                                </a>

                                <!-- x -->
                                <a href="https://x.com/your_likhwezi_account" class="social-icon-link x-twitter" target="_blank" rel="noopener noreferrer" aria-label="Visit Likhwezi Technologies on X" title="X">
                                    <i class="bi bi-twitter-x" aria-hidden="true"></i>
                                </a>

                                <!-- tiktok -->
                                <a href="https://www.tiktok.com/@your_likhwezi_account" class="social-icon-link tiktok" target="_blank" rel="noopener noreferrer" aria-label="Visit Likhwezi Technologies on TikTok" title="TikTok">
                                    <i class="bi bi-tiktok" aria-hidden="true"></i>
                                </a>
                                */ ?>
                            </div>
                        </div>
                    </aside>

                    <!-- enquiry form -->
                    <div class="contact-form-section" id="enquiry-form">

                        <!-- form heading -->
                        <h2 class="section-title">Book a consultation today</h2>

                        <!-- keeps the form readable on larger screens -->
                        <div class="contact-form-wrap">

                            <!-- error message when the whole submission fails -->
                            <?php if ($formError !== ''): ?>
                                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($formError) ?></div>
                            <?php elseif (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">Please correct the highlighted fields below.</div>
                            <?php endif; ?>

                            <!-- sends back to contact.php which checks and saves the enquiry -->
                            <form id="contactForm" method="post" action="contact.php" novalidate>
                                <input type="hidden" name="enquiryToken" value="<?= htmlspecialchars($_SESSION['enquiryToken']) ?>">

                                <!-- spam trap that bots fill in so their submission is dropped -->
                                <div class="contact-trap" aria-hidden="true">
                                    <label for="website">Leave this field empty</label>
                                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                                </div>

                                <!-- name, phone, email and organisation sit two to a row when there is room -->
                                <div class="contact-form-fields">

                                    <!-- full name -->
                                    <div class="mb-4">
                                        <label for="name" class="form-label">Full name <span aria-hidden="true">*</span></label>
                                        <input type="text" class="form-control<?= field_class($errors, 'name') ?>" id="name" name="name" value="<?= htmlspecialchars($values['name']) ?>"<?= field_describedby($errors, 'name') ?> placeholder="Enter your full name" maxlength="150" autocomplete="name" required>
                                        <?= field_error($errors, 'name') ?>
                                    </div>

                                    <!-- phone number -->
                                    <div class="mb-4">
                                        <label for="phone" class="form-label">Phone number <span aria-hidden="true">*</span></label>
                                        <input type="tel" class="form-control<?= field_class($errors, 'phone') ?>" id="phone" name="phone" value="<?= htmlspecialchars($values['phone']) ?>"<?= field_describedby($errors, 'phone') ?> placeholder="Enter your phone number" maxlength="12" inputmode="numeric" autocomplete="tel" required>
                                        <?= field_error($errors, 'phone') ?>
                                    </div>

                                    <!-- email -->
                                    <div class="mb-4">
                                        <label for="email" class="form-label">Email address <span aria-hidden="true">*</span></label>
                                        <input type="email" class="form-control<?= field_class($errors, 'email') ?>" id="email" name="email" value="<?= htmlspecialchars($values['email']) ?>"<?= field_describedby($errors, 'email') ?> placeholder="Enter your email address" maxlength="255" autocomplete="email" required>
                                        <?= field_error($errors, 'email') ?>
                                    </div>

                                    <!-- organisation -->
                                    <div class="mb-4">
                                        <label for="organisation" class="form-label">Organisation</label>
                                        <input type="text" class="form-control<?= field_class($errors, 'organisation') ?>" id="organisation" name="organisation" value="<?= htmlspecialchars($values['organisation']) ?>"<?= field_describedby($errors, 'organisation') ?> placeholder="Enter your organisation" maxlength="255" autocomplete="organization">
                                        <?= field_error($errors, 'organisation') ?>
                                    </div>
                                </div>

                                <!-- meeting type, shown as toggle buttons -->
                                <fieldset class="mb-4">
                                    <legend class="form-label">Preferred meeting type <span aria-hidden="true">*</span></legend>

                                    <div class="meeting-type-options">
                                        <!-- virtual meeting -->
                                        <input type="radio" class="meeting-type-input" id="meetingTypeVirtual" name="meetingType" value="virtual" required <?= $values['meetingType'] === 'virtual' ? 'checked' : '' ?>>
                                        <label class="meeting-type-button" for="meetingTypeVirtual">Virtual meeting</label>

                                        <!-- face to face meeting -->
                                        <input type="radio" class="meeting-type-input" id="meetingTypePhysical" name="meetingType" value="physical" <?= $values['meetingType'] === 'physical' ? 'checked' : '' ?>>
                                        <label class="meeting-type-button" for="meetingTypePhysical">Face-to-face meeting</label>
                                    </div>
                                    <?= field_error($errors, 'meetingType') ?>
                                </fieldset>

                                <!-- how can we help -->
                                <div class="mb-4">
                                    <label for="description" class="form-label">How can we help? <span aria-hidden="true">*</span></label>
                                    <textarea class="form-control<?= field_class($errors, 'description') ?>" id="description" name="description"<?= field_describedby($errors, 'description') ?> rows="6" placeholder="Describe your enquiry" maxlength="5000" required><?= htmlspecialchars($values['description']) ?></textarea>
                                    <?= field_error($errors, 'description') ?>
                                </div>

                                <!-- organisation is the only optional field -->
                                <p class="small text-muted">Fields marked * are required.</p>

                                <!-- privacy consent -->
                                <div class="form-check contact-consent mb-4 grid-flex">
                                    <input type="checkbox" class="form-check-input<?= field_class($errors, 'privacyConsent') ?>" id="privacyConsent" name="privacyConsent" value="1" required <?= $values['privacyConsent'] ? 'checked' : '' ?><?= field_describedby($errors, 'privacyConsent') ?>>

                                    <div class="consent-copy">
                                        <label class="form-check-label" for="privacyConsent">I have read the</label>

                                        <!-- opens the privacy notice without ticking the box -->
                                        <button type="button" class="privacy-link" data-privacy-modal-open>Privacy Notice and Consent</button>

                                        <span>and agree to Likhwezi Technologies using my details to respond to this enquiry, in line with POPIA.</span>
                                    </div>
                                    <?= field_error($errors, 'privacyConsent') ?>
                                </div>

                                <!-- submit button -->
                                <button type="submit" class="btn btn-primary">Submit enquiry</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <!-- thank you popup, shown after the form is submitted -->
            <div class="contact-modal-overlay<?= $enquirySubmitted ? ' is-visible' : '' ?>" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successModalTitle" aria-hidden="<?= $enquirySubmitted ? 'false' : 'true' ?>">
                <div class="contact-modal-box">
                    <!-- close button -->
                    <button type="button" class="contact-modal-close" id="closeSuccessModal" aria-label="Close confirmation">&times;</button>

                    <!-- heading -->
                    <h2 id="successModalTitle">Thank you for your enquiry</h2>

                    <p>Your enquiry has been received. Please expect an email from the Likhwezi Technologies team within 48 hours.</p>
                    <p>Your patience is highly appreciated.</p>

                    <!-- ok button -->
                    <div class="contact-modal-actions">
                        <button type="button" class="btn btn-primary" id="okSuccessModal">OK</button>
                    </div>
                </div>
            </div>
        </main>

        <!-- privacy notice popup -->
        <?php include 'includes/components/privacyModal.php'; ?>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>

        <script src="assets/js/contact.js?v=<?= filemtime(__DIR__ . '/assets/js/contact.js') ?>"></script>
    </body>
</html>
