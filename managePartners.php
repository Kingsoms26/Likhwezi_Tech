<?php
    session_start();
    include 'tools/dbConnection.php';

    if (!isset($_SESSION['accountID'])) {
        header('Location: staffLogin.php');
        exit;
    }

    $pageTitle = "Manage Partners";
    $editingPartner = null;
    $errors = [];

    // ---- Archive / Restore ----
    if (isset($_GET['toggleArchive']) && ctype_digit($_GET['toggleArchive'])) {
        $id = (int) $_GET['toggleArchive'];

        $stmt = $conn->prepare("SELECT isArchived FROM Partner WHERE partnerID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();

        if ($current) {
            $newState = $current['isArchived'] ? 0 : 1;
            $action = $newState ? 'archived' : 'restored';

            $update = $conn->prepare("UPDATE Partner SET isArchived = ? WHERE partnerID = ?");
            $update->bind_param("ii", $newState, $id);
            $update->execute();

            $log = $conn->prepare("INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, ?)");
            $log->bind_param("iis", $id, $_SESSION['accountID'], $action);
            $log->execute();
        }

        header("Location: managePartners.php");
        exit;
    }

    // ---- Add / Edit submission ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $websiteURL = trim($_POST['websiteURL'] ?? '');
        $partnerID = $_POST['partnerID'] ?? null;

        if ($name === '') {
            $errors[] = "Partner name is required.";
        } elseif (!$partnerID) {
            // Only check for duplicates when adding a NEW partner —
            // editing an existing one is allowed to keep its own name.
            $checkStmt = $conn->prepare("SELECT partnerID FROM Partner WHERE name = ?");
            $checkStmt->bind_param("s", $name);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $errors[] = "A partner named \"$name\" already exists.";
            }
        }

        $logoFilename = null;
        if (!empty($_FILES['logo']['name'])) {
            $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = "Logo must be a PNG, JPG or WEBP file.";
            } else {
                $logoFilename = uniqid('partner_') . '.' . $ext;
                $destination = __DIR__ . '/images/partners/' . $logoFilename;
                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                    $errors[] = "Failed to upload logo image.";
                    $logoFilename = null;
                }
            }
        }

        if (empty($errors)) {
            if ($partnerID) {
                // Editing an existing partner
                if ($logoFilename) {
                    $stmt = $conn->prepare(
                        "UPDATE Partner SET name = ?, description = ?, logo = ?, websiteURL = ? WHERE partnerID = ?"
                    );
                    $stmt->bind_param("ssssi", $name, $description, $logoFilename, $websiteURL, $partnerID);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE Partner SET name = ?, description = ?, websiteURL = ? WHERE partnerID = ?"
                    );
                    $stmt->bind_param("sssi", $name, $description, $websiteURL, $partnerID);
                }
                $stmt->execute();
            } else {
                // Creating a new partner — two-step insert, matches the
                // ArchivableEntity/Partner shared-ID pattern in the schema
                if (!$logoFilename) {
                    $errors[] = "A logo image is required for a new partner.";
                } else {
                    $conn->begin_transaction();
                    $conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Partner')");
                    $newEntityID = $conn->insert_id;

                    $stmt = $conn->prepare(
                        "INSERT INTO Partner (partnerID, name, description, logo, websiteURL, isArchived) VALUES (?, ?, ?, ?, ?, FALSE)"
                    );
                    $stmt->bind_param("issss", $newEntityID, $name, $description, $logoFilename, $websiteURL);
                    $stmt->execute();
                    $conn->commit();
                }
            }

            if (empty($errors)) {
                header("Location: managePartners.php");
                exit;
            }
        }
    }

    // ---- Load a partner into the edit form if requested ----
    if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
        $id = (int) $_GET['edit'];
        $stmt = $conn->prepare("SELECT partnerID, name, description, logo, websiteURL FROM Partner WHERE partnerID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editingPartner = $stmt->get_result()->fetch_assoc();
    }

    // ---- Full list, including archived, so staff can restore ----
    $allPartners = $conn->query("SELECT partnerID, name, description, logo, isArchived FROM Partner ORDER BY dateAdd DESC");

    include 'components/header.php';
?>

<body>
    <?php include 'components/navBar.php'; ?>

    <section class="section-container d-flex flex-column align-items-center py-3 mx-10">
        <div class="d-flex justify-content-between align-items-center w-100" style="max-width: 900px;">
            <div class="section-title py-2"><?= $editingPartner ? 'Edit Partner' : 'Add a Partner' ?></div>
            <a href="logout.php" class="footer-link" style="color: #0389E1;">Log out</a>
        </div>

        <?php foreach ($errors as $error): ?>
            <p style="color: #C0392B;"><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>

        <form method="POST" enctype="multipart/form-data" class="partner-form">
            <?php if ($editingPartner): ?>
                <input type="hidden" name="partnerID" value="<?= $editingPartner['partnerID'] ?>">
            <?php endif; ?>

            <label>Partner Name</label>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($editingPartner['name'] ?? '') ?>">

            <label>Description</label>
            <textarea name="description" rows="3"><?= htmlspecialchars($editingPartner['description'] ?? '') ?></textarea>

            <label>Website URL (optional)</label>
            <input type="url" name="websiteURL" placeholder="https://example.com"
                   value="<?= htmlspecialchars($editingPartner['websiteURL'] ?? '') ?>">

            <label>Logo<?= $editingPartner ? ' (leave blank to keep current logo)' : '' ?></label>
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp">

            <button type="submit" class="btn btn-primary mt-2">
                <?= $editingPartner ? 'Save Changes' : 'Add Partner' ?>
            </button>
            <?php if ($editingPartner): ?>
                <a href="managePartners.php" class="btn btn-sm mt-2">Cancel</a>
            <?php endif; ?>
        </form>

        <table class="partner-manage-table mt-5">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $allPartners->fetch_assoc()): ?>
                    <tr>
                        <td><img src="images/partners/<?= htmlspecialchars($row['logo']) ?>" alt="" class="manage-logo-thumb"></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= $row['isArchived'] ? 'Archived' : 'Active' ?></td>
                        <td>
                            <a href="managePartners.php?edit=<?= $row['partnerID'] ?>">Edit</a>
                            &nbsp;|&nbsp;
                            <a href="managePartners.php?toggleArchive=<?= $row['partnerID'] ?>">
                                <?= $row['isArchived'] ? 'Restore' : 'Archive' ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <?php include 'components/footer.php'; ?>
</body>
</html>