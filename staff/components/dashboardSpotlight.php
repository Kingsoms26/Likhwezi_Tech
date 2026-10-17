<?php
    // Shared feature area for Admin and Staff dashboards.
    // The activity panel is global for Admins and limited to their own actions for Staff.
    $spotlightSlides = $spotlightSlides ?? [];
    $spotlightEnquiryStats = $spotlightEnquiryStats ?? ['new' => 0, 'contacted' => 0, 'closed' => 0];
    $recentActivity = $recentActivity ?? [];
    $canViewFullActivityLog = ($account['role'] ?? '') === 'Admin';
    $spotlightMax = max(1, (int) max($spotlightEnquiryStats));
?>

<section class="dashboard-showcase row g-3" aria-label="Workspace highlights">
    <div class="col-12 col-xl-7">
        <section class="dashboard-spotlight" data-dashboard-spotlight aria-roledescription="carousel" aria-label="Enquiries, campaigns and registrations">
            <div class="spotlight-heading d-flex align-items-start justify-content-between gap-3">
                <div>
                    <span class="spotlight-kicker">WORKSPACE HIGHLIGHTS</span>
                    <p class="spotlight-heading-note">A quick look at what needs attention.</p>
                </div>
                <span class="spotlight-counter" data-spotlight-counter>01 / <?= str_pad((string) max(1, count($spotlightSlides)), 2, '0', STR_PAD_LEFT) ?></span>
            </div>

            <div class="spotlight-stage" aria-live="off">
                <?php foreach ($spotlightSlides as $index => $slide) : ?>
                    <article class="spotlight-slide spotlight-tone-<?= htmlspecialchars($slide['tone']) ?><?= $index === 0 ? ' is-active' : '' ?>"
                             data-spotlight-slide="<?= (int) $index ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                        <div class="spotlight-copy">
                            <span class="spotlight-eyebrow"><span></span><?= htmlspecialchars($slide['eyebrow']) ?></span>
                            <h2><?= htmlspecialchars($slide['title']) ?></h2>
                            <p class="spotlight-description"><?= htmlspecialchars($slide['description']) ?></p>
                            <div class="spotlight-metric">
                                <strong><?= htmlspecialchars($slide['value']) ?></strong>
                                <span><?= htmlspecialchars($slide['label']) ?></span>
                            </div>
                            <a class="btn btn-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 fw-semibold spotlight-action" href="<?= htmlspecialchars($slide['href']) ?>">
                                <?= htmlspecialchars($slide['action']) ?>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="spotlight-art" aria-hidden="true">
                            <div class="spotlight-art-glow"></div>
                            <div class="spotlight-art-card spotlight-art-card-back"></div>
                            <div class="spotlight-art-card spotlight-art-card-middle"></div>
                            <div class="spotlight-art-card spotlight-art-card-front">
                                <div class="spotlight-art-brand">
                                    <img class="spotlight-art-logo" src="assets/images/logo-branding.jpeg" alt="">
                                    <span>LIKHWEZI TECH</span>
                                </div>
                                <span class="spotlight-art-icon"><i class="bi bi-<?= htmlspecialchars($slide['icon']) ?>"></i></span>
                                <strong><?= htmlspecialchars($slide['value']) ?></strong>
                                <small><?= htmlspecialchars($slide['label']) ?></small>
                                <span class="spotlight-art-dots"><i></i><i></i><i></i></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="spotlight-footer d-flex align-items-center justify-content-between gap-3">
                <span class="spotlight-footer-label">FEATURED AREA</span>
                <div class="spotlight-controls d-flex align-items-center gap-2" aria-label="Choose dashboard highlight">
                    <?php foreach ($spotlightSlides as $index => $slide) : ?>
                        <button type="button" class="spotlight-dot<?= $index === 0 ? ' is-active' : '' ?>"
                                data-spotlight-go="<?= (int) $index ?>"
                                aria-label="Show <?= htmlspecialchars(strtolower($slide['eyebrow'])) ?>"
                                aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                    <?php endforeach; ?>
                    <button type="button" class="spotlight-arrow" data-spotlight-prev aria-label="Previous highlight">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="spotlight-arrow" data-spotlight-next aria-label="Next highlight">
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-5">
        <div class="dashboard-side-widgets row g-3">
            <div class="col-12">
                <section class="card dashboard-widget enquiry-status-widget" aria-labelledby="enquiry-status-title">
                    <div class="widget-heading d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h2 id="enquiry-status-title">Enquiry status</h2>
                            <p>Current open and closed requests</p>
                        </div>
                        <a class="d-flex align-items-center justify-content-center" href="manageEnquiries.php" aria-label="View all enquiries"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="status-bars d-grid gap-2">
                        <?php
                            $statusRows = [
                                ['New', (int) ($spotlightEnquiryStats['new'] ?? 0), 'status-bar-new'],
                                ['Contacted', (int) ($spotlightEnquiryStats['contacted'] ?? 0), 'status-bar-contacted'],
                                ['Closed', (int) ($spotlightEnquiryStats['closed'] ?? 0), 'status-bar-closed']
                            ];
                        ?>
                        <?php foreach ($statusRows as [$label, $count, $class]) : ?>
                            <div class="status-bar-row">
                                <div class="status-bar-label d-flex align-items-center justify-content-between gap-2"><span><?= htmlspecialchars($label) ?></span><strong><?= $count ?></strong></div>
                                <div class="progress status-bar-track" aria-label="<?= htmlspecialchars($label) ?> enquiries">
                                    <div class="progress-bar <?= $class ?>" role="progressbar" aria-label="<?= htmlspecialchars($label) ?> enquiries" aria-valuemin="0" aria-valuemax="<?= $spotlightMax ?>" aria-valuenow="<?= $count ?>" style="width: <?= min(100, ($count / $spotlightMax) * 100) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="col-12">
                <section class="card dashboard-widget activity-log-widget" aria-labelledby="recent-activity-title">
                    <div class="widget-heading activity-log-heading d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h2 id="recent-activity-title">Activity log</h2>
                            <p><?= $canViewFullActivityLog ? 'Latest changes across the workspace' : 'Your recent account activity' ?></p>
                        </div>
                        <?php if ($canViewFullActivityLog) : ?>
                            <a class="d-flex align-items-center justify-content-center" href="activityLog.php" aria-label="View full activity log"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>

                    <?php if (!$recentActivity) : ?>
                        <p class="activity-log-empty">No activity has been recorded yet.</p>
                    <?php else : ?>
                        <ol class="activity-log-list list-unstyled d-grid gap-2 p-0 mb-0">
                            <?php foreach ($recentActivity as $activity) : ?>
                                <?php
                                    $activityCategory = (string) ($activity['category'] ?? '');
                                    $activityIcon = match ($activityCategory) {
                                        'Enquiry' => 'chat-left-text',
                                        'Registration' => 'person-plus',
                                        'Campaign' => 'megaphone',
                                        'Account' => 'people',
                                        'Login' => 'shield-check',
                                        'Profile' => 'person-circle',
                                        'Event' => 'calendar-event',
                                        'Announcement' => 'broadcast',
                                        'Content' => 'window-stack',
                                        default => 'activity'
                                    };
                                    $activityKind = match ($activityCategory) {
                                        'Enquiry' => 'enquiry',
                                        'Registration' => 'registration',
                                        'Campaign' => 'campaign',
                                        'Account' => 'account',
                                        'Login' => 'login',
                                        default => 'general'
                                    };
                                    $activityTime = strtotime((string) ($activity['createdAt'] ?? ''));
                                    $activityActor = trim((string) ($activity['fullName'] ?? '')) ?: (string) ($activity['username'] ?? 'Staff');
                                ?>
                                <li class="activity-log-item d-grid align-items-center gap-2">
                                    <span class="activity-log-icon activity-log-icon-<?= $activityKind ?> d-flex align-items-center justify-content-center" aria-hidden="true">
                                        <i class="bi bi-<?= $activityIcon ?>"></i>
                                    </span>
                                    <span class="activity-log-copy d-flex flex-column gap-1">
                                        <strong><?= htmlspecialchars((string) ($activity['description'] ?? 'Workspace activity')) ?></strong>
                                        <small><?= htmlspecialchars($activityCategory ?: 'Activity') ?> · <?= htmlspecialchars($activityActor) ?></small>
                                    </span>
                                    <time class="d-flex flex-column align-items-end" datetime="<?= $activityTime ? htmlspecialchars(date(DATE_ATOM, $activityTime)) : '' ?>">
                                        <?= $activityTime ? htmlspecialchars(date('j M', $activityTime)) : '—' ?>
                                        <small><?= $activityTime ? htmlspecialchars(date('H:i', $activityTime)) : '' ?></small>
                                    </time>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    <?php if ($canViewFullActivityLog) : ?>
                        <a class="activity-log-view-all d-inline-flex align-items-center gap-2 align-self-start" href="activityLog.php">View full activity log <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</section>

<script src="assets/js/dashboardSpotlight.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboardSpotlight.js') ?>" defer></script>
