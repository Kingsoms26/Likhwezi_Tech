<?php
    // profileMenu.php is the profile button in the dashboard header
    // contains the settings menu and the profile popover

    // use the session details when the page has no account loaded
    $menuAccount = $account ?? [
        'username'        => $_SESSION['username'] ?? 'Username',
        'role'            => $_SESSION['role'] ?? null,
        'firstName'       => $_SESSION['firstName'] ?? null,
        'lastName'        => $_SESSION['lastName'] ?? null,
        'profileImageURL' => $_SESSION['profileImage'] ?? null,
    ];

    $menuName = accountDisplayName($menuAccount);
    $menuRole = $menuAccount['role'] ?? 'No role assigned';

    // format a date the same way profile.php does
    $menuDate = fn (?string $value, string $format) => $value ? date($format, strtotime($value)) : 'Never';

    // countdown to the next 6 monthly password change
    $menuPasswordAge = isset($account) ? passwordAge($account) : null;
?>

<div class="profile-menu">

    <!-- profile button, opens the settings menu -->
    <button type="button" class="dashboard-profile" id="settings-menu-toggle"
            aria-haspopup="true" aria-expanded="false" aria-controls="settings-menu">
        <?= profileAvatar($menuAccount, 'profile-avatar') ?>
        <span class="dashboard-user"><?= htmlspecialchars($menuName) ?></span>
        <?php if ($menuPasswordAge && $menuPasswordAge['status'] !== 'ok') : ?>
            <!-- dot on the avatar while a password change is due soon or overdue -->
            <span class="profile-alert-dot password-<?= $menuPasswordAge['status'] ?>"
                  title="Password change <?= htmlspecialchars(strtolower(passwordAgeLabel($menuPasswordAge))) ?>"></span>
        <?php endif; ?>
    </button>

    <!-- settings menu -->
    <div class="header-popover settings-menu" id="settings-menu" hidden>
        <div class="popover-identity">
            <?= profileAvatar($menuAccount, 'profile-avatar') ?>
            <div>
                <div class="popover-name"><?= htmlspecialchars($menuName) ?></div>
                <div class="popover-muted"><?= htmlspecialchars($menuRole) ?></div>
            </div>
        </div>

        <p class="popover-label">Settings</p>

        <button type="button" class="popover-item" id="profile-popover-open" aria-haspopup="dialog" aria-controls="profile-popover">My Profile</button>
        <a href="logout.php" class="popover-item popover-item-danger">Log Out</a>
    </div>

    <!-- profile popover showing the full account -->
    <?php if (isset($account)) : ?>
        <div class="header-popover profile-popover" id="profile-popover"
             role="dialog" aria-labelledby="profile-popover-title" hidden>
            <div class="profile-popover-header">
                <button type="button" class="popover-icon-button" id="profile-popover-back" aria-label="Back to settings">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M15.4 5.4 14 4l-8 8 8 8 1.4-1.4L8.8 12z"/>
                    </svg>
                </button>
                <h2 id="profile-popover-title">My Profile</h2>
                <button type="button" class="popover-icon-button" id="profile-popover-close" aria-label="Close">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/>
                    </svg>
                </button>
            </div>

            <div class="profile-popover-body">
                <div class="profile-identity">
                    <?= profileAvatar($menuAccount, 'profile-photo') ?>
                    <div>
                        <div class="profile-name"><?= htmlspecialchars($menuName) ?></div>
                        <div class="profile-role"><?= htmlspecialchars($menuRole) ?></div>
                        <span class="status-badge status-<?= htmlspecialchars($account['accountStatus']) ?>"><?= htmlspecialchars(ucfirst($account['accountStatus'])) ?></span>
                    </div>
                </div>

                <dl class="detail-list">
                    <dt>First name</dt>
                    <dd><?= htmlspecialchars($account['firstName'] ?: '—') ?></dd>

                    <dt>Last name</dt>
                    <dd><?= htmlspecialchars($account['lastName'] ?: '—') ?></dd>

                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($account['email']) ?></dd>

                    <dt>Username</dt>
                    <dd><?= htmlspecialchars($account['username']) ?></dd>

                    <dt>Last login</dt>
                    <dd><?= htmlspecialchars($menuDate($account['lastLogin'], 'j M Y, H:i')) ?></dd>

                    <dt>Password changed</dt>
                    <dd><?= htmlspecialchars($menuDate($account['passwordChangedAt'], 'j M Y, H:i')) ?></dd>
                </dl>

                <?php include __DIR__ . '/passwordReminder.php'; ?>
            </div>

            <div class="profile-popover-footer">
                <a href="profile.php#personal-details" class="form-button">Edit profile</a>
                <a href="profile.php#change-password" class="popover-link">Change password</a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="assets/js/profileMenu.js?v=<?= filemtime(__DIR__ . '/../assets/js/profileMenu.js') ?>"></script>
