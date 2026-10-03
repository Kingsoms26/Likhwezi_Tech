<?php
    // staffLogin.php is the staff login page
    // checks the username or email and password then sends the staff member to their dashboard
    session_start();

    // only connect to the database when a login is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include 'config/dbConnection.php';
    }

    $error = '';

    // find the account and its staff role, the role comes from whichever staff table holds the account
    // an account in none of them has no role and cannot log in
    $stmt = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$conn->connect_error) {
        $stmt = $conn->prepare(
            "SELECT u.accountID, u.username, u.passwordHash, u.accountStatus, u.isArchived,
                    CASE
                        WHEN a.accountID IS NOT NULL THEN 'Admin'
                        WHEN m.accountID IS NOT NULL THEN 'Marketing'
                        WHEN s.accountID IS NOT NULL THEN 'Customer Service'
                    END AS role
             FROM UserAccount u
             LEFT JOIN Admin a ON a.accountID = u.accountID
             LEFT JOIN StaffMarketing m ON m.accountID = u.accountID
             LEFT JOIN StaffCustomerService s ON s.accountID = u.accountID
             WHERE u.username = ? OR u.email = ?"
        );
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$stmt) {
        error_log('Staff login: database unavailable: ' . ($conn->connect_error ?: $conn->error));
        $error = 'Login is unavailable right now. Please try again in a few minutes.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // look up the account
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        // same message for every failure so the form does not reveal which accounts exist
        if ($account
            && $account['accountStatus'] === 'active'
            && !$account['isArchived']
            && $account['role'] !== null
            && password_verify($password, $account['passwordHash'])
        ) {
            // log them in
            session_regenerate_id(true);
            $_SESSION['accountID'] = $account['accountID'];
            $_SESSION['username'] = $account['username'];
            $_SESSION['role'] = $account['role'];

            // save the last login time and record it in the activity log
            $lastLogin = $conn->prepare("UPDATE UserAccount SET lastLogin = NOW() WHERE accountID = ?");
            if ($lastLogin) {
                $lastLogin->bind_param("i", $account['accountID']);
                $lastLogin->execute();
            }

            require_once 'staff/helpers/activityLog.php';
            logActivity($conn, 'Login', 'login', 'Logged in', (int) $account['accountID']);

            // staff who must change their password are sent to profile.php by the dashboard
            header('Location: staff/index.php');
            exit;
        } else {
            $error = "Incorrect username/email or password.";

            // log the failed attempt and the reason against the matched account
            // the typed name is only stored when it matched an account so mistyped passwords never end up in the log
            if ($account) {
                $reason = match (true) {
                    (bool) $account['isArchived']         => 'account is archived',
                    $account['accountStatus'] !== 'active' => 'account is ' . $account['accountStatus'],
                    $account['role'] === null              => 'account has no staff role',
                    default                                => 'wrong password',
                };
                require_once 'staff/helpers/activityLog.php';
                logActivity($conn, 'Login', 'failed', "Failed login: $reason", (int) $account['accountID'],
                    ['accountID' => (int) $account['accountID'], 'username' => $account['username']]);
            }
        }
    }

    $pageTitle = "Staff Login";
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <!-- login card -->
        <section class="staff-login">
            <div class="staff-login-card">
                <img src="assets/images/logo/logo-branding.webp" alt="Likhwezi Technologies logo" class="staff-login-logo">
                <h1 class="staff-login-title">Staff Login</h1>

                <!-- error message -->
                <?php if ($error): ?>
                    <div class="staff-login-error" role="alert">
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- microsoft sign in goes here once the entra app and oauth callback are set up
                     the callback must only log in existing active accounts matched by email -->

                <!-- login form -->
                <form method="POST" class="staff-login-form">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required autofocus>

                    <label for="password" class="form-label">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                </form>
            </div>
        </section>

        <script>
            // show or hide the password
            document.querySelector('.password-toggle').addEventListener('click', function () {
                const input = document.getElementById('password');
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                this.setAttribute('aria-pressed', String(!showing));
                this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                this.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        </script>

        <!-- footer, staff pages do not show the cookie notice -->
        <?php $hideCookieNotice = true; ?>
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>