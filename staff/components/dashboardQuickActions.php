<?php
    // Dashboard shortcuts are role-aware, just like the sidebar.
    $quickActionRole = $account['role'] ?? 'Staff';
    $quickActions = $quickActionRole === 'Admin'
        ? [
            ['Accounts', 'Review staff access', 'accounts.php', 'people', 'blue'],
            ['Website content', 'Update site pages', 'manageContent.php', 'layout-text-window', 'violet'],
            ['Announcements', 'Publish an update', 'announcements.php', 'megaphone', 'amber'],
            ['Activity log', 'Review recent changes', 'activityLog.php', 'clock-history', 'green'],
        ]
        : [
            ['Enquiries', 'Respond to requests', 'manageEnquiries.php', 'envelope', 'blue'],
            ['Registrations', 'Review new sign-ups', 'registrations.php', 'person-plus', 'violet'],
            ['Campaigns', 'Track fundraising', 'manageCampaigns.php', 'megaphone', 'amber'],
            ['Gallery & Events', 'Manage upcoming events', 'manageEvents.php', 'calendar-event', 'green'],
        ];
?>

<section class="dashboard-quick-actions" aria-labelledby="quick-actions-title">
    <div class="quick-actions-heading">
        <div>
            <h2 id="quick-actions-title">Quick actions</h2>
            <p>Jump straight into a common task.</p>
        </div>
    </div>

    <div class="quick-action-grid">
        <?php foreach ($quickActions as [$label, $description, $href, $icon, $tone]) : ?>
            <a class="quick-action-card quick-action-<?= htmlspecialchars($tone) ?>" href="<?= htmlspecialchars($href) ?>">
                <span class="quick-action-icon" aria-hidden="true"><i class="bi bi-<?= htmlspecialchars($icon) ?>"></i></span>
                <span class="quick-action-copy">
                    <strong><?= htmlspecialchars($label) ?></strong>
                    <small><?= htmlspecialchars($description) ?></small>
                </span>
                <i class="bi bi-arrow-up-right quick-action-arrow" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
</section>
