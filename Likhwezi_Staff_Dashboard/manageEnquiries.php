<?php
/*
 * ENQUIRIES (staff dashboard page)
 *
 * This page owns the Enquiries content. It is role-neutral on purpose:
 * the same page can be linked from the Admin and Customer Service
 * dashboards, so it uses its own wrapper class (.enquiries-page) rather
 * than a role wrapper such as .admin-dashboard.
 *
 * Flow:
 *     session + login check
 *          -> handle any form actions (before any HTML is output)
 *          -> get data from tools/enquiryData.php
 *          -> include shared shell
 *          -> write Enquiries content
 *          -> close the page structure
 */

session_start();

/* Only logged-in staff may see this page. */
if (!isset($_SESSION['accountID'])) {
    header('Location: ../staffLogin.php');
    exit;
}

require __DIR__ . '/../tools/dbConnection.php';
require __DIR__ . '/tools/enquiryData.php';

$showArchived = isset($_GET['showArchived']);

/*
 * Form actions. These run before the shell is included, because a redirect
 * only works while nothing has been printed yet. Redirecting back to the
 * same URL afterwards stops a page refresh from re-submitting the form.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enquiryID = (int) ($_POST['enquiryID'] ?? 0);
    $accountID = (int) $_SESSION['accountID'];

    if (isset($_POST['updateStatus'])) {
        updateEnquiryStatus($conn, $enquiryID, $_POST['status'] ?? '', $accountID);
    } elseif (isset($_POST['toggleArchive'])) {
        toggleEnquiryArchive($conn, $enquiryID, $accountID);
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$counts = getEnquiryCounts($conn);
$enquiries = getEnquiries($conn, $showArchived);

$pageTitle = 'Enquiries';
$activePage = 'enquiries';

/* Open the shared dashboard shell. */
include __DIR__ . '/components/dashboard.php';
?>

<div class="enquiries-page">

    <section class="dashboard-cards">

        <?php
        $cardTitle = 'New';
        $cardValue = $counts['new'];
        $cardMeta = 'Waiting for a first response';
        $cardClass = 'enquiries-new-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        $cardTitle = 'Contacted';
        $cardValue = $counts['contacted'];
        $cardMeta = 'Response sent, in progress';
        $cardClass = 'enquiries-contacted-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        $cardTitle = 'Closed';
        $cardValue = $counts['closed'];
        $cardMeta = 'Resolved';
        $cardClass = 'enquiries-closed-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <section class="dashboard-panel enquiries-panel">

        <div class="panel-header">
            <h2>All Enquiries</h2>
            <a class="panel-button" href="manageEnquiries.php<?= $showArchived ? '' : '?showArchived=1' ?>">
                <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
            </a>
        </div>

        <div class="panel-body">
            <div class="dashboard-table-wrap">
                <table class="dashboard-table">

                    <thead>
                        <tr>
                            <th>Contact</th>
                            <th>Meeting</th>
                            <th>Message</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($enquiries)) : ?>
                            <tr>
                                <td colspan="6" class="enquiries-empty">No enquiries to show.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($enquiries as $enquiry) : ?>
                            <tr class="<?= $enquiry['isArchived'] ? 'enquiry-row-archived' : '' ?>">

                                <td class="enquiry-contact">
                                    <strong><?= htmlspecialchars($enquiry['name']) ?></strong>
                                    <span><?= htmlspecialchars($enquiry['email']) ?></span>
                                    <?php if (!empty($enquiry['phoneNumber'])) : ?>
                                        <span><?= htmlspecialchars($enquiry['phoneNumber']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($enquiry['companyName'])) : ?>
                                        <span><?= htmlspecialchars($enquiry['companyName']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars(ucfirst($enquiry['meetingType'])) ?></td>

                                <td title="<?= htmlspecialchars($enquiry['description']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($enquiry['description'], 0, 90, '...')) ?>
                                </td>

                                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($enquiry['dateCreated']))) ?></td>

                                <td>
                                    <form method="post">
                                        <input type="hidden" name="enquiryID" value="<?= (int) $enquiry['enquiryID'] ?>">
                                        <input type="hidden" name="updateStatus" value="1">
                                        <select name="status" class="enquiry-status-select" onchange="this.form.submit()">
                                            <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $value => $label) : ?>
                                                <option value="<?= $value ?>" <?= $enquiry['status'] === $value ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>

                                <td>
                                    <form method="post">
                                        <input type="hidden" name="enquiryID" value="<?= (int) $enquiry['enquiryID'] ?>">
                                        <input type="hidden" name="toggleArchive" value="1">
                                        <button type="submit" class="enquiry-action-button">
                                            <?= $enquiry['isArchived'] ? 'Restore' : 'Archive' ?>
                                        </button>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </section>

</div>


<!--
Page end
-->
</main>

</body>
</html>