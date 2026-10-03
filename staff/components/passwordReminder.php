<?php
    // passwordReminder.php shows a countdown to the next 6 monthly password change
    // shared by the profile popover and profile.php, needs $account from requireStaff()

    $passwordAge = passwordAge($account);
    $reminderDays = abs($passwordAge['daysLeft']);
    $reminderUnit = $reminderDays === 1 ? 'day' : 'days';

    // pick the reminder wording based on how many days are left
    if ($passwordAge['status'] === 'overdue') {
        $reminderText = "Password change overdue by $reminderDays $reminderUnit";
    } elseif ($passwordAge['daysLeft'] === 0) {
        $reminderText = 'Change password today';
    } else {
        $reminderText = "Change password in $reminderDays $reminderUnit";
    }
?>

<p class="password-reminder password-<?= $passwordAge['status'] ?>" role="status"><?= htmlspecialchars($reminderText) ?></p>
