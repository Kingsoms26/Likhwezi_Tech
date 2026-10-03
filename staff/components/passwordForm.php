<!-- passwordForm.php is the change password form on the profile page -->
<form method="POST" class="dashboard-form panel-padding">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="changePassword">

    <div class="form-field">
        <label for="current-password">Current password</label>
        <input type="password" id="current-password" name="currentPassword" required autocomplete="current-password">
    </div>

    <div class="form-field">
        <label for="new-password">New password <span class="field-hint">(at least 8 characters, with a letter and a number)</span></label>
        <input type="password" id="new-password" name="newPassword" required minlength="8" maxlength="72" autocomplete="new-password">
    </div>

    <div class="form-field">
        <label for="confirm-password">Confirm new password</label>
        <input type="password" id="confirm-password" name="confirmPassword" required minlength="8" maxlength="72" autocomplete="new-password">
    </div>

    <button type="submit" class="form-button">Change password</button>
</form>
