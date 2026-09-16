<?php
    session_start();
    include 'tools/dbConnection.php';

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare(
            "SELECT accountID, username, passwordHash, accountStatus FROM UserAccount WHERE username = ? OR email = ?"
        );
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if ($account && $account['accountStatus'] === 'active' && password_verify($password, $account['passwordHash'])) {
            $_SESSION['accountID'] = $account['accountID'];
            $_SESSION['username'] = $account['username'];
            header('Location: managePartners.php');
            exit;
        } else {
            $error = "Incorrect username/email or password.";
        }
    }

    $pageTitle = "Staff Login";
    include 'components/header.php';
?>

<body>
    <?php include 'components/navBar.php'; ?>

    <section class="section-container d-flex flex-column align-items-center py-3 mx-10">
        <div class="section-title py-2">Staff Login</div>

        <?php if ($error): ?>
            <p style="color: #C0392B;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="partner-form">
            <label>Username or Email</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-primary mt-2">Log In</button>
        </form>
    </section>

    <?php include 'components/footer.php'; ?>
</body>
</html>