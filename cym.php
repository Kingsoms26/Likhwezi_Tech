<?php
    // cym.php is the cyber young minds page
    // contains the hero section with the photo carousel, the programme banner, the overview and the registration form
    require_once __DIR__ . '/includes/security.php';

    $pageTitle = "Cyber Young Minds";
    $navTitle = "Cyber Young Minds";
    $navSubtitle = "by Likhwezi Technologies";
    $pageLogo = "assets/images/logo/cym-logo.jpg";
    $pageLogoAlt = "Cyber Young Minds logo";

    // the programme's own site, the button to it only shows once the site is live
    require_once __DIR__ . '/includes/helpers/siteStatus.php';
    require_once __DIR__ . '/includes/helpers/siteContent.php';
    // fetch the contact email, cym site address and participant count, admins edit them on staff/manageContent.php
    $site = siteSettings();
    $cymSiteURL = $site['cymSiteURL'];
    $cymSiteOnline = siteIsOnline($cymSiteURL);

    // turn a phone number into 10 digits starting with 0, or null if it is not valid
    function normalise_phone(string $input): ?string {
        $d = preg_replace('/\D/', '', $input);
        if (str_starts_with($d, '27')) $d = '0' . substr($d, 2);
        if (str_starts_with($d, '0027')) $d = '0' . substr($d, 4);
        return preg_match('/^0[1-8]\d{8}$/', $d) ? $d : null;
    }

    // save a registration, the archivable entity is saved first then the registration with the same id
    function save_registration(mysqli $conn, array $r): bool {
        $conn->begin_transaction();

        if (!$conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Registration')")) {
            $conn->rollback();
            return false;
        }

        $registrationID = $conn->insert_id;

        $stmt = $conn->prepare(
            "INSERT INTO Registration (registrationID, firstName, lastName, age, email, phoneNumber, programme,
                consentConfirmation, mediaConsent, guardianName, guardianLastName, guardianRelationship,
                guardianPhoneNumber, guardianConsentConfirmation, guardianConsentGivenAt, guardianCommunicationConsent)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, IF(? = 1, NOW(), NULL), ?)"
        );

        if (!$stmt) {
            $conn->rollback();
            return false;
        }

        $stmt->bind_param(
            "ississsissssiii",
            $registrationID,
            $r['firstName'],
            $r['lastName'],
            $r['age'],
            $r['email'],
            $r['phone'],
            $r['programme'],
            $r['mediaConsent'],
            $r['guardianName'],
            $r['guardianLastName'],
            $r['guardianRelationship'],
            $r['guardianPhone'],
            $r['guardianConsent'],
            $r['guardianConsent'],
            $r['guardianCommunicationConsent']
        );

        if (!$stmt->execute()) {
            $conn->rollback();
            return false;
        }

        return $conn->commit();
    }

    // shown when there are no programmes in the database yet or the database is down
    const DEFAULT_CYM_PROGRAMMES = ['Coding', 'Artificial Intelligence', 'Robotics', 'Hackathons'];

    // programmes the admin has made active in their chosen order
    function load_programmes(?mysqli $conn): array {
        if (!$conn || $conn->connect_error) {
            return DEFAULT_CYM_PROGRAMMES;
        }

        $result = $conn->query("SELECT name FROM Programme WHERE isActive = TRUE ORDER BY sortOrder, name");
        if (!$result) {
            error_log('CYM page: could not load programmes: ' . $conn->error);
            return DEFAULT_CYM_PROGRAMMES;
        }

        $programmes = array_column($result->fetch_all(MYSQLI_ASSOC), 'name');
        return $programmes ?: DEFAULT_CYM_PROGRAMMES;
    }

    // visitors must wait this many seconds between registrations
    const REGISTRATION_COOLDOWN_SECONDS = 30;

    // the form is answered with json so php warnings would break it
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        ini_set('display_errors', 0);
    }

    // fetch the programmes, cached for 5 minutes so viewing the page skips the database
    require_once __DIR__ . '/includes/helpers/cache.php';
    $programmes = cached('cym_programmes', 300, function () {
        $conn = db();
        return $conn ? load_programmes($conn) : null;
    }) ?? DEFAULT_CYM_PROGRAMMES;

    // only connect to the database when a registration is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn = db();
    }

    // token that ties each submission to a form this site served
    if (empty($_SESSION['registrationToken'])) {
        $_SESSION['registrationToken'] = bin2hex(random_bytes(32));
    }

    // handle the registration form, the answer is json so the popup can show the result
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $respond = function (array $body, int $status = 200): void {
            http_response_code($status);
            echo json_encode($body);
            exit;
        };

        // bots fill in the hidden website field, pretend it worked so they move on
        if (trim($_POST['website'] ?? '') !== '') {
            $respond(['ok' => true]);
        }

        // check the token and the wait between registrations
        if (!hash_equals($_SESSION['registrationToken'], $_POST['registrationToken'] ?? '')) {
            $respond(['ok' => false, 'message' => 'Your session expired. Please refresh the page and submit the form again.'], 400);
        }
        if (time() - ($_SESSION['lastRegistrationAt'] ?? 0) < REGISTRATION_COOLDOWN_SECONDS) {
            $respond(['ok' => false, 'message' => 'You have just registered. Please wait a moment before registering again.'], 429);
        }

        // read the form
        $v = [
            'firstName' => trim($_POST['name'] ?? ''),
            'lastName' => trim($_POST['lastName'] ?? ''),
            'age' => trim($_POST['age'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phoneNumber'] ?? ''),
            'programme' => $_POST['program'] ?? '',
            'guardianName' => trim($_POST['guardianName'] ?? ''),
            'guardianLastName' => trim($_POST['guardianLastName'] ?? ''),
            'guardianRelationship' => trim($_POST['relationship'] ?? ''),
            'guardianPhone' => trim($_POST['guardianPhoneNumber'] ?? ''),
        ];
        $errors = [];

        // check the participant's details
        if ($v['firstName'] === '' || mb_strlen($v['firstName']) > 100) {
            $errors['name'] = 'Please enter your name (100 characters or fewer).';
        }
        if ($v['lastName'] === '' || mb_strlen($v['lastName']) > 100) {
            $errors['lastName'] = 'Please enter your last name (100 characters or fewer).';
        }

        $age = filter_var($v['age'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        if ($age === false) {
            $errors['age'] = 'Please enter a valid age.';
        }

        if ($v['email'] !== '' && (mb_strlen($v['email']) > 255 || !filter_var($v['email'], FILTER_VALIDATE_EMAIL))) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        $phone = $v['phone'] !== '' ? normalise_phone($v['phone']) : null;
        if ($v['phone'] !== '' && $phone === null) {
            $errors['phoneNumber'] = 'Please enter a valid South African phone number, e.g. 082 123 4567.';
        }
        if ($v['email'] === '' && $v['phone'] === '') {
            $errors['email'] = 'Please enter an email address or phone number.';
        }

        if (!in_array($v['programme'], $programmes, true)) {
            $errors['program'] = 'Please choose a programme.';
        }

        if (($_POST['consent-main'] ?? '') === '') {
            $errors['consent-main'] = 'Please confirm that you have read the Privacy Notice and Consent.';
        }

        // guardian details are needed for under 18s
        $isMinor = $age !== false && $age < 18;
        $guardianPhone = null;
        if ($isMinor) {
            if ($v['guardianName'] === '' || mb_strlen($v['guardianName']) > 150) {
                $errors['guardianName'] = 'Please enter the guardian\'s first name.';
            }
            if ($v['guardianLastName'] === '' || mb_strlen($v['guardianLastName']) > 100) {
                $errors['guardianLastName'] = 'Please enter the guardian\'s last name.';
            }
            if ($v['guardianRelationship'] === '' || mb_strlen($v['guardianRelationship']) > 100) {
                $errors['relationship'] = 'Please enter the guardian\'s relationship to the participant.';
            }
            $guardianPhone = normalise_phone($v['guardianPhone']);
            if ($guardianPhone === null) {
                $errors['guardianPhoneNumber'] = 'Please enter a valid South African phone number for the guardian.';
            }
            if (($_POST['guardian-consent'] ?? '') === '') {
                $errors['guardian-consent'] = 'The guardian must confirm the Privacy Notice and Consent.';
            }
        }

        if ($errors) {
            $respond(['ok' => false, 'errors' => $errors, 'message' => 'Please check the highlighted fields.'], 422);
        }

        // save the registration, guardian details are only kept for under 18s
        $saved = isset($conn) && !$conn->connect_error && save_registration($conn, [
            'firstName' => $v['firstName'],
            'lastName' => $v['lastName'],
            'age' => $age,
            'email' => $v['email'] !== '' ? $v['email'] : null,
            'phone' => $phone,
            'programme' => $v['programme'],
            'mediaConsent' => ($_POST['media-consent'] ?? '') !== '' ? 1 : 0,
            'guardianName' => $isMinor ? $v['guardianName'] : null,
            'guardianLastName' => $isMinor ? $v['guardianLastName'] : null,
            'guardianRelationship' => $isMinor ? $v['guardianRelationship'] : null,
            'guardianPhone' => $isMinor ? $guardianPhone : null,
            'guardianConsent' => $isMinor ? 1 : 0,
            'guardianCommunicationConsent' => $isMinor && ($_POST['guardianConsent'] ?? '') !== '' ? 1 : 0,
        ]);

        if (!$saved) {
            error_log('CYM form: registration not saved' . (isset($conn) ? ': ' . $conn->error : ''));
            $respond(['ok' => false, 'message' => 'We could not save your registration right now. Please try again, or email us at ' . $site['contactEmail'] . '.'], 500);
        }

        // remember when they registered for the wait
        $_SESSION['lastRegistrationAt'] = time();
        $respond(['ok' => true]);
    }
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>
    <body class="cym-page">
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <main class="cym-page-main">

            <!-- hero section, programme intro on the left and the photo carousel on the right -->
            <section class="hero cym-hero">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <h1>Building the minds that build the future</h1>
                        <p>Cyber Young Minds runs programmes catered to the youth, in coding, robotics, and AI for learners, their teachers, and anyone in the community who wants to start. It's all funded by Likhwezi Technologies' consultancy work and by sponsors, which is why it costs participants nothing.</p>
                    </div>
                    <div class="p-1">
                        <a href="#modal-overlay" class="btn btn-primary cym-register-cta register-btn" aria-haspopup="dialog">Register</a>
                    </div>
                </div>

                <!-- photos from cym sessions, managed on staff/manageContent.php -->
                <div class="hero-image cym-photos">
                    <?php
                        $carouselID = 'cymPhotoCarousel';
                        $carouselPhotos = cymPhotos();
                        include 'includes/components/photoCarousel.php';
                    ?>
                </div>
            </section>

            <!-- scrolling programme banner -->
            <section class="banner" aria-label="Coding and technology programmes banner">
                <?php
                    // the banner repeats the programme names from the registration form
                    $bannerItems = $programmes;
                    $bannerLabel = count($bannerItems) > 1
                        ? implode(', ', array_slice($bannerItems, 0, -1)) . ' and ' . end($bannerItems)
                        : implode('', $bannerItems);
                ?>
                <div class="banner-track" aria-label="<?= htmlspecialchars($bannerLabel) ?>">
                    <?php for ($group = 0; $group < 2; $group++): ?>
                        <div class="banner-group"<?= $group === 1 ? ' aria-hidden="true"' : '' ?>>
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <?php foreach ($bannerItems as $item): ?>
                                    <span class="banner-item"><?= htmlspecialchars($item) ?></span>
                                <?php endforeach; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </section>

            <!-- programme overview, intro on the left and stats on the right -->
            <section class="page-section cym-about" id="overview">
                <div class="cym-about-grid">
                    <div class="cym-about-text">
                        <h3 class="cym-lead">Most young people meet technology as something that happens to them. We think they should meet it as something they can take apart and participate in.</h3>
                        <p>Coding, Robotics, and AI, taught by the people who do it for a living. No experience needed, no laptop needed, nothing to pay.</p>
                    </div>

                    <div>
                        <div class="cym-stats">
                            <div class="cym-stat">
                                <span class="cym-stat-number"><?= count($programmes) ?></span>
                                <span class="cym-stat-label">Programmes to participate in</span>
                            </div>
                            <div class="cym-stat">
                                <span class="cym-stat-number"><?= htmlspecialchars($site['cymParticipants']) ?></span>
                                <span class="cym-stat-label">Registered participants</span>
                            </div>
                            <div class="cym-stat">
                                <span class="cym-stat-number">R0</span>
                                <span class="cym-stat-label">Charged to participants</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <hr>

            <!-- registration, explanation on the left and what the programme site offers on the right -->
            <section class="page-section cym-join">
                <div class="cym-join-grid">
                    <div class="cym-about-text">
                        <h3 class="cym-section-heading">Register for a programme or event</h3>
                        <p>Joining Cyber Young Minds gives you hands-on time with coding, robotics and AI, guided by people who work in technology every day. You learn by building real projects alongside other learners, and you leave with skills you can keep using at school, at work and in your community.</p>
                        <p>There's no experience needed, no laptop needed and nothing to pay.</p>
                        <div class="cym-join-buttons">
                            <a href="#modal-overlay" class="btn btn-primary cym-register-cta register-btn" aria-haspopup="dialog">Register</a>
                            <?php if ($cymSiteOnline): ?>
                                <a href="<?= htmlspecialchars($cymSiteURL) ?>" class="btn btn-ghost" target="_blank" rel="noopener">Go to Cyber Young Minds</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="cym-join-card">
                        <p class="cym-site-name"><?= htmlspecialchars(preg_replace('/^www\./', '', parse_url($cymSiteURL, PHP_URL_HOST) ?: $cymSiteURL)) ?></p>
                        <h6>What you'll find there</h6>
                        <ul class="cym-site-list">
                            <li>Session times and dates</li>
                            <li>Photos and recaps of previous sessions</li>
                            <li>Contact Details</li>
                        </ul>
                    </div>
                </div>
            </section>
        </main>

        <!-- registration popup -->
        <div class="modal-overlay" id="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="register-title">
            <div class="modal-box" id="register">
                <button type="button" class="modal-close" id="modal-close" aria-label="Close registration form">&times;</button>
                <div class="cym-register">
                    <h3 id="register-title">Register for Cyber Young Minds</h3>
                    <p class="subtitle">Fill in your details and we'll be in touch with session times and venue info.</p>

                    <form id="register-form" method="post" action="cym.php" data-contact-email="<?= htmlspecialchars($site['contactEmail']) ?>">
                        <input type="hidden" name="registrationToken" value="<?= htmlspecialchars($_SESSION['registrationToken']) ?>">

                        <!-- spam trap that bots fill in so their submission is dropped -->
                        <div class="contact-trap" aria-hidden="true">
                            <label for="website">Leave this field empty</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <!-- error message -->
                        <div class="form-message" id="register-message" role="alert" tabindex="-1" hidden></div>

                        <!-- participant details -->
                        <div class="form-row">
                            <div class="form-field">
                                <label for="register-fname">First Name</label>
                                <input type="text" id="register-fname" name="name" placeholder="Enter your first name" required>
                            </div>
                            <div class="form-field">
                                <label for="register-lname">Last Name</label>
                                <input type="text" id="register-lname" name="lastName" placeholder="Enter your last name (Surname)" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label for="register-age">Age</label>
                                <input type="number" id="register-age" name="age" placeholder="Enter your age" min="0" required>
                            </div>
                            <div class="form-field">
                                <label for="reg-email">Email <span class="field-note">(optional if phone is provided)</span></label>
                                <input type="email" id="reg-email" name="email" placeholder="you@email.com">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label for="phone">Phone number <span class="field-note">(optional if email is provided)</span></label>
                                <input type="tel" id="phone" name="phoneNumber" placeholder="012 345 6789" maxlength="12" inputmode="numeric">
                            </div>
                            <div class="form-field">
                                <label for="register-program">Programme</label>
                                <select id="register-program" name="program" required>
                                    <option value="">Select Programme</option>
                                    <?php foreach ($programmes as $programme): ?>
                                        <option value="<?= htmlspecialchars($programme) ?>"><?= htmlspecialchars($programme) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- consent -->
                        <label class="consent">
                            <input type="checkbox" name="consent-main" required>
                            <span>I have read the <a href="#privacyModal" data-privacy-modal-open>Privacy Notice and Consent</a> and agree to Likhwezi Technologies using my details to manage this Cyber Young Minds registration, in line with POPIA.</span>
                        </label>

                        <label class="consent">
                            <input type="checkbox" name="media-consent">
                            <span><strong>Media consent (optional):</strong> I consent to Likhwezi Technologies using photographs, videos, testimonials, and recordings taken during programmes, training sessions, events, or activities for promotional, educational, reporting, and marketing purposes.</span>
                        </label>

                        <!-- guardian details, only shown for under 18s -->
                        <div class="guardian-fields" id="guardian-fields" hidden>
                            <hr>
                            <h4>Guardian details are required for participants under 18.</h4>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="guardian-fname">Guardian First Name</label>
                                    <input type="text" id="guardian-fname" name="guardianName" placeholder="Guardian first name">
                                </div>
                                <div class="form-field">
                                    <label for="guardian-lname">Guardian Last Name</label>
                                    <input type="text" id="guardian-lname" name="guardianLastName" placeholder="Guardian surname">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="register-guardRela">Relationship to Participant</label>
                                    <input type="text" id="register-guardRela" name="relationship" placeholder="e.g. Parent, Aunt, Uncle, Grandparent">
                                </div>
                                <div class="form-field">
                                    <label for="guardian-phone">Phone number</label>
                                    <input type="tel" id="guardian-phone" name="guardianPhoneNumber" placeholder="012 345 6789" maxlength="12" inputmode="numeric">
                                </div>
                            </div>

                            <label class="consent">
                                <input type="checkbox" name="guardian-consent" required>
                                <span>I have read the <a href="#privacyModal" data-privacy-modal-open>Privacy Notice and Consent</a> and agree to Likhwezi Technologies using my details to manage this Cyber Young Minds registration, in line with POPIA.</span>
                            </label>

                            <label class="consent">
                                <input type="checkbox" name="guardianConsent" required>
                                <span>I, as the guardian of the registered participant, consent to my information being used for the purpose of registration and communication.</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- privacy notice popup -->
        <?php include 'includes/components/privacyModal.php'; ?>

        <!-- success popup -->
        <div class="modal-overlay" id="success-modal" role="dialog" aria-modal="true" aria-labelledby="success-title">
            <div class="modal-box success-modal-box">
                <button type="button" class="modal-close success-modal-close" aria-label="Close success message">&times;</button>
                <h3 id="success-title">Registration received</h3>
                <div class="success-content">
                    <p>Thank you for registering for Cyber Young Minds.</p>
                    <p>We have received your details and will contact you with the next steps, dates, and venue information.</p>
                </div>
                <div class="success-modal-actions">
                    <button type="button" class="btn btn-primary success-close-btn">Close</button>
                </div>
            </div>
        </div>

        <script src="assets/js/cym.js?v=<?= filemtime(__DIR__ . '/assets/js/cym.js') ?>"></script>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>
