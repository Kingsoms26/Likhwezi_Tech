<?php
    // profile.php is the staff member's own profile page
    // contains the account details, personal details, change password and their last 10 actions
    // new accounts and admin resets only see the set up your account form until it is done
    // email, username, role and status can only be changed by an admin on accounts.php

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';

    // make sure the staff member is logged in
    $account = requireStaff($conn);
    $accountID = (int) $account['accountID'];
    $mustChangePassword = $account['mustChangePassword'];

    $errors = [];
    $passwordErrors = [];
    $formInput = null;

    // handle the forms

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            setFlash('error', 'Your session expired. Please try again.');
            header('Location: profile.php');
            exit;
        }

        // which form was sent
        $action = $_POST['action'] ?? '';

        // save the personal details
        if ($action === 'updateProfile' && !$mustChangePassword) {
            $formInput = [
                'firstName'   => $_POST['firstName'] ?? '',
                'lastName'    => $_POST['lastName'] ?? '',
            ];
            $errors = validatePersonalDetails($formInput);

            // only save the photo once the name fields are valid
            $photoPath = null;
            if (!$errors) {
                $upload = saveProfilePhoto($_FILES['profilePhoto'] ?? []);
                $photoPath = $upload['path'];
                if ($upload['error']) {
                    $errors[] = $upload['error'];
                }
            }

            if (!$errors) {
                if (updateOwnProfile($conn, $accountID, $formInput, $photoPath)) {
                    if ($photoPath) {
                        deleteProfilePhoto($account['profileImageURL']);
                    }
                    setFlash('success', 'Your profile has been updated.');
                    logActivity($conn, 'Profile', 'updated', 'Updated own profile' . ($photoPath ? ' and photo' : ''), $accountID);
                    header('Location: profile.php');
                    exit;
                }

                deleteProfilePhoto($photoPath);
                $errors[] = 'Your profile could not be saved. Please try again.';
            }
        }

        // first login set up, name and new password
        if ($action === 'completeSetup' && $mustChangePassword) {
            $formInput = [
                'firstName' => $_POST['firstName'] ?? '',
                'lastName'  => $_POST['lastName'] ?? '',
            ];
            $passwordErrors = completeAccountSetup(
                $conn,
                $accountID,
                $formInput,
                $_POST['newPassword'] ?? '',
                $_POST['confirmPassword'] ?? ''
            );

            if (!$passwordErrors) {
                session_regenerate_id(true);
                logActivity($conn, 'Profile', 'setup', 'Completed account setup and set a new password', $accountID);
                header('Location: index.php');
                exit;
            }
        }

        // change password
        if ($action === 'changePassword' && !$mustChangePassword) {
            $passwordErrors = changeOwnPassword(
                $conn,
                $accountID,
                $_POST['currentPassword'] ?? '',
                $_POST['newPassword'] ?? '',
                $_POST['confirmPassword'] ?? ''
            );

            if (!$passwordErrors) {
                session_regenerate_id(true);
                setFlash('success', 'Your password has been changed.');
                logActivity($conn, 'Profile', 'password', 'Changed own password', $accountID);
                header('Location: profile.php');
                exit;
            }
        }
    }

    // page data

    $flash = takeFlash();
    $form = $formInput ?? $account;
    $activity = $mustChangePassword ? [] : getRecentActivity($conn, $accountID);

    $pageTitle = $mustChangePassword ? 'Set Up Your Account' : 'My Profile';
    $activePage = $mustChangePassword ? '' : 'profile';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<?php if ($mustChangePassword) : ?>

    <!-- first login or after an admin reset, name and new password and nothing else until it is done -->
    <div class="change-password-page">

        <!-- error messages -->
        <?php $errors = $passwordErrors; include __DIR__ . '/components/dashboardAlerts.php'; ?>

        <!-- set up form -->
        <section class="dashboard-panel narrow-panel">
            <div class="panel-header">
                <h2>Set up your account</h2>
            </div>

            <p class="panel-padding panel-intro">
                Enter your name and choose a new password before you can use the staff portal.
            </p>

            <form method="POST" class="dashboard-form panel-padding">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="completeSetup">

                <div class="form-row">
                    <div class="form-field">
                        <label for="first-name">First name</label>
                        <input type="text" id="first-name" name="firstName" maxlength="100" required
                               autocomplete="given-name" value="<?= htmlspecialchars($form['firstName'] ?? '') ?>">
                    </div>

                    <div class="form-field">
                        <label for="last-name">Last name</label>
                        <input type="text" id="last-name" name="lastName" maxlength="100" required
                               autocomplete="family-name" value="<?= htmlspecialchars($form['lastName'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-field">
                    <label for="new-password">New password <span class="field-hint">(at least 8 characters, with a letter and a number)</span></label>
                    <input type="password" id="new-password" name="newPassword" required minlength="8" maxlength="72" autocomplete="new-password">
                </div>

                <div class="form-field">
                    <label for="confirm-password">Confirm new password</label>
                    <input type="password" id="confirm-password" name="confirmPassword" required minlength="8" maxlength="72" autocomplete="new-password">
                </div>

                <button type="submit" class="form-button">Save and continue</button>
            </form>
        </section>

    </div>

<?php else : ?>

    <!-- profile page -->
    <div class="profile-page">

        <!-- success and error messages -->
        <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

        <div class="profile-layout">

            <!-- account details, only an admin can change these -->
            <section class="dashboard-panel profile-summary-panel">
                <div class="panel-header">
                    <h2>Account</h2>
                </div>

                <div class="panel-padding">
                    <div class="profile-identity">
                        <img class="profile-photo" src="<?= htmlspecialchars(profileImageSrc($account['profileImageURL'])) ?>" alt="">
                        <div>
                            <div class="profile-name"><?= htmlspecialchars(accountDisplayName($account)) ?></div>
                            <div class="profile-role"><?= htmlspecialchars($account['role'] ?? 'No role assigned') ?></div>
                        </div>
                    </div>

                    <dl class="detail-list">
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars($account['email']) ?></dd>

                        <dt>Username</dt>
                        <dd><?= htmlspecialchars($account['username']) ?></dd>

                        <dt>Status</dt>
                        <dd><span class="status-badge status-<?= htmlspecialchars($account['accountStatus']) ?>"><?= htmlspecialchars(ucfirst($account['accountStatus'])) ?></span></dd>

                        <dt>Created</dt>
                        <dd><?= htmlspecialchars(date('j M Y', strtotime($account['dateCreated']))) ?></dd>

                        <dt>Created by</dt>
                        <dd><?= htmlspecialchars($account['createdByName']) ?></dd>

                        <dt>Last login</dt>
                        <dd><?= $account['lastLogin'] ? htmlspecialchars(date('j M Y, H:i', strtotime($account['lastLogin']))) : 'Never' ?></dd>

                        <dt>Password changed</dt>
                        <dd><?= $account['passwordChangedAt'] ? htmlspecialchars(date('j M Y, H:i', strtotime($account['passwordChangedAt']))) : 'Never' ?></dd>
                    </dl>

                    <p class="field-hint">To change your email, username or role, contact an administrator.</p>
                </div>
            </section>

            <div class="profile-forms">

                <!-- personal details -->
                <section class="dashboard-panel" id="personal-details">
                    <div class="panel-header">
                        <h2>Personal details</h2>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="dashboard-form panel-padding">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="updateProfile">

                        <div class="form-row">
                            <div class="form-field">
                                <label for="first-name">First name</label>
                                <input type="text" id="first-name" name="firstName" maxlength="100" required
                                       autocomplete="given-name" value="<?= htmlspecialchars($form['firstName'] ?? '') ?>">
                            </div>

                            <div class="form-field">
                                <label for="last-name">Last name</label>
                                <input type="text" id="last-name" name="lastName" maxlength="100" required
                                       autocomplete="family-name" value="<?= htmlspecialchars($form['lastName'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- profile photo upload -->
                        <?php
                        $dropzoneID = 'profile-photo';
                        $dropzoneName = 'profilePhoto';
                        $dropzoneLabel = 'Profile photo';
                        include __DIR__ . '/components/photoDropzone.php';
                        ?>

                        <button type="submit" class="form-button">Save details</button>
                    </form>
                </section>

                <!-- change password -->
                <section class="dashboard-panel" id="change-password">
                    <div class="panel-header">
                        <h2>Change password</h2>
                    </div>

                    <?php if ($passwordErrors) : ?>
                        <div class="panel-padding panel-alert">
                            <?php $errors = $passwordErrors; $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                        </div>
                    <?php endif; ?>

                    <div class="panel-padding panel-alert">
                        <?php include __DIR__ . '/components/passwordReminder.php'; ?>
                    </div>

                    <?php include __DIR__ . '/components/passwordForm.php'; ?>
                </section>

            </div>
        </div>

        <!-- my activity, last 10 actions newest first -->
        <section class="dashboard-panel activity-panel">
            <div class="panel-header">
                <h2>My activity</h2>
            </div>

            <?php if (!$activity) : ?>
                <p class="panel-padding panel-empty">No activity yet.</p>
            <?php else : ?>
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Activity</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity as $item) : ?>
                                <tr>
                                    <td><span class="status-badge activity-<?= htmlspecialchars(strtolower($item['type'])) ?>"><?= htmlspecialchars($item['type']) ?></span></td>
                                    <td><?= htmlspecialchars($item['summary']) ?></td>
                                    <td><?= $item['happenedAt'] ? htmlspecialchars(date('j M Y, H:i', strtotime($item['happenedAt']))) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>

<?php endif; ?>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>