<?php
    // enquiryTable.php is the enquiries table used by manageEnquiries.php and closedEnquiries.php
    // needs $tableEnquiries, $tableEmpty and $account which decides claim or release
    $tableEnquiries = $tableEnquiries ?? [];
    $tableEmpty = $tableEmpty ?? 'No enquiries to show.';
    // only customer service can claim enquiries
    $canClaim = $account['role'] === 'Customer Service';
    $myAccountID = (int) $account['accountID'];
?>

<div class="dashboard-table-wrap">
    <table class="dashboard-table enquiries-table">

        <!-- column headings -->
        <thead>
            <tr>
                <th>Contact</th>
                <th>Meeting</th>
                <th>Message</th>
                <th>Received</th>
                <th>Status</th>
                <th>Handled by</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

            <!-- shown when there are no enquiries -->
            <?php if (empty($tableEnquiries)) : ?>
                <tr>
                    <td colspan="7" class="enquiries-empty"><?= htmlspecialchars($tableEmpty) ?></td>
                </tr>
            <?php endif; ?>

            <!-- a row for each enquiry -->
            <?php foreach ($tableEnquiries as $enquiry) : ?>
                <?php
                $isMine = (int) $enquiry['handledBy'] === $myAccountID;
                $isUnclaimed = $enquiry['handledBy'] === null;
                ?>
                <tr class="enquiry-row enquiry-status-<?= htmlspecialchars($enquiry['status']) ?> <?= $enquiry['isArchived'] ? 'enquiry-row-archived' : '' ?>">

                    <!-- contact details -->
                    <td class="enquiry-contact">
                        <strong><?= htmlspecialchars($enquiry['name']) ?></strong>
                        <span><a href="mailto:<?= htmlspecialchars($enquiry['email']) ?>"><?= htmlspecialchars($enquiry['email']) ?></a></span>
                        <?php if (!empty($enquiry['companyName'])) : ?>
                            <span class="enquiry-company"><?= htmlspecialchars($enquiry['companyName']) ?></span>
                        <?php endif; ?>
                    </td>

                    <td class="enquiry-meeting" data-label="Meeting"><?= htmlspecialchars(ucfirst($enquiry['meetingType'])) ?></td>

                    <td class="enquiry-message-cell" title="<?= htmlspecialchars($enquiry['description']) ?>">
                        <?= htmlspecialchars(mb_strimwidth($enquiry['description'], 0, 90, '...')) ?>
                    </td>

                    <td class="enquiry-received" data-label="Received"><?= htmlspecialchars(date('d M Y, H:i', strtotime($enquiry['dateCreated']))) ?></td>

                    <!-- status, saves as soon as it changes -->
                    <td class="enquiry-status-cell">
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="enquiryID" value="<?= (int) $enquiry['enquiryID'] ?>">
                            <input type="hidden" name="updateStatus" value="1">
                            <select name="status" class="enquiry-status-select" data-submit-on-change>
                                <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $value => $label) : ?>
                                    <option value="<?= $value ?>" <?= $enquiry['status'] === $value ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>

                    <!-- who is handling it -->
                    <td class="enquiry-handler <?= $isUnclaimed ? 'is-unclaimed' : '' ?>" data-label="Handled by">
                        <?php if ($isUnclaimed) : ?>
                            Unclaimed
                        <?php elseif ($isMine) : ?>
                            You
                        <?php else : ?>
                            <?= htmlspecialchars($enquiry['handledByName'] ?? 'Unknown') ?>
                        <?php endif; ?>
                    </td>

                    <!-- view, claim, release and archive buttons -->
                    <td class="enquiry-actions-cell">
                        <div class="enquiry-actions">
                            <button type="button" class="enquiry-action-button enquiry-view"
                                    data-enquiry="<?= htmlspecialchars(json_encode([
                                        'name'        => $enquiry['name'],
                                        'companyName' => $enquiry['companyName'] ?: '—',
                                        'email'       => $enquiry['email'],
                                        'phoneNumber' => $enquiry['phoneNumber'] ?: '—',
                                        'meetingType' => ucfirst($enquiry['meetingType']),
                                        'received'    => date('d M Y, H:i', strtotime($enquiry['dateCreated'])),
                                        'status'      => ucfirst($enquiry['status']),
                                        'handledBy'   => $isUnclaimed ? 'Unclaimed' : ($isMine ? 'You' : ($enquiry['handledByName'] ?? 'Unknown')),
                                        'description' => $enquiry['description'],
                                    ], JSON_INVALID_UTF8_SUBSTITUTE)) ?>">
                                View
                            </button>

                            <?php if ($canClaim && $isUnclaimed && !$enquiry['isArchived']) : ?>
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="enquiryID" value="<?= (int) $enquiry['enquiryID'] ?>">
                                    <input type="hidden" name="claim" value="1">
                                    <button type="submit" class="enquiry-action-button enquiry-claim">Claim</button>
                                </form>
                            <?php elseif ($isMine) : ?>
                                <form method="post">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="enquiryID" value="<?= (int) $enquiry['enquiryID'] ?>">
                                    <input type="hidden" name="release" value="1">
                                    <button type="submit" class="enquiry-action-button">Release</button>
                                </form>
                            <?php endif; ?>

                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="enquiryID" value="<?= (int) $enquiry['enquiryID'] ?>">
                                <input type="hidden" name="toggleArchive" value="1">
                                <button type="submit" class="enquiry-action-button">
                                    <?= $enquiry['isArchived'] ? 'Restore' : 'Archive' ?>
                                </button>
                            </form>
                        </div>
                    </td>

                </tr>
            <?php endforeach; ?>

        </tbody>
    </table>
</div>
