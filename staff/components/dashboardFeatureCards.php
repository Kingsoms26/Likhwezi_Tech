<?php
    // Four large workspace cards with live values supplied by each dashboard page.
    $dashboardFeatureCards = $dashboardFeatureCards ?? [];
?>

<section
    class="dashboard-feature-cards row row-cols-1 row-cols-md-2 row-cols-xxl-4 g-3"
    aria-label="Main workspace areas"
>
    <?php foreach ($dashboardFeatureCards as $card) : ?>
        <div class="col">
            <a
                class="card dashboard-feature-card h-100 p-3 p-xl-4 dashboard-feature-card-<?= htmlspecialchars($card['key']) ?>"
                href="<?= htmlspecialchars($card['href']) ?>"
            >
                <div class="feature-card-top d-flex align-items-center justify-content-between gap-3">
                    <span class="feature-card-icon d-flex align-items-center justify-content-center">
                        <i class="bi bi-<?= htmlspecialchars($card['icon']) ?>" aria-hidden="true"></i>
                    </span>
                    <span class="feature-card-arrow d-flex align-items-center justify-content-center" aria-hidden="true">
                        <i class="bi bi-arrow-up-right"></i>
                    </span>
                </div>

                <div class="feature-card-heading d-flex flex-wrap align-items-baseline justify-content-between gap-2">
                    <h2><?= htmlspecialchars($card['title']) ?></h2>
                    <span><?= htmlspecialchars($card['eyebrow']) ?></span>
                </div>

                <div class="feature-card-value d-flex align-items-baseline gap-2">
                    <strong><?= htmlspecialchars((string) $card['value']) ?></strong>
                    <span><?= htmlspecialchars($card['unit']) ?></span>
                </div>
                <p class="feature-card-summary"><?= htmlspecialchars($card['summary']) ?></p>

                <div class="feature-card-visual feature-card-visual-<?= htmlspecialchars($card['visual']) ?>">
                    <?php if ($card['visual'] === 'enquiries') : ?>
                        <?php
                            $segments = $card['segments'] ?? [];
                            $segmentTotal = max(0, array_sum(array_column($segments, 'value')));
                        ?>
                        <div class="progress feature-enquiry-bar" aria-hidden="true">
                            <?php foreach ($segments as $segment) : ?>
                                <?php
                                    $segmentWidth = $segmentTotal > 0
                                        ? round(((int) $segment['value'] / $segmentTotal) * 100, 2)
                                        : 0;
                                ?>
                                <div
                                    class="progress-bar feature-enquiry-segment feature-segment-<?= htmlspecialchars($segment['key']) ?>"
                                    style="width: <?= $segmentWidth ?>%"
                                ></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="feature-enquiry-legend d-flex flex-wrap gap-2">
                            <?php foreach ($segments as $segment) : ?>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="feature-legend-dot feature-segment-<?= htmlspecialchars($segment['key']) ?>"></i>
                                    <?= htmlspecialchars($segment['label']) ?>
                                    <b><?= (int) $segment['value'] ?></b>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($card['visual'] === 'events') : ?>
                        <div class="feature-event-preview d-flex align-items-center gap-3">
                            <i class="bi bi-calendar2-event d-flex align-items-center justify-content-center" aria-hidden="true"></i>
                            <span class="feature-preview-copy d-flex flex-column gap-1">
                                <small><?= !empty($card['detail']) ? 'NEXT UP' : 'CALENDAR' ?></small>
                                <strong><?= htmlspecialchars((string) ($card['detail'] ?: 'Nothing scheduled')) ?></strong>
                                <span><?= htmlspecialchars((string) ($card['detailMeta'] ?? '')) ?></span>
                            </span>
                        </div>
                    <?php elseif ($card['visual'] === 'registrations') : ?>
                        <div class="feature-registrations-preview d-flex align-items-center gap-3">
                            <span class="feature-document-icon d-flex align-items-center justify-content-center">
                                <i class="bi bi-file-earmark-person" aria-hidden="true"></i>
                            </span>
                            <span class="feature-preview-copy d-flex flex-column gap-1">
                                <small>LAST 7 DAYS</small>
                                <strong><?= htmlspecialchars((string) ($card['recentValue'] ?? 0)) ?> new sign-ups</strong>
                                <span>Programme registrations</span>
                            </span>
                        </div>
                    <?php elseif ($card['visual'] === 'campaigns') : ?>
                        <?php if (!empty($card['progressLabel'])) : ?>
                            <div class="feature-campaign-preview">
                                <div class="feature-campaign-heading d-flex align-items-center justify-content-between gap-2">
                                    <span><?= htmlspecialchars((string) $card['progressLabel']) ?></span>
                                    <strong><?= (int) ($card['progress'] ?? 0) ?>%</strong>
                                </div>
                                <div class="progress feature-campaign-track" aria-label="Featured campaign progress">
                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="<?= max(0, min(100, (int) ($card['progress'] ?? 0))) ?>"
                                        style="width: <?= max(0, min(100, (int) ($card['progress'] ?? 0))) ?>%"
                                    ></div>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="feature-campaign-empty d-flex align-items-center justify-content-center gap-3">
                                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                <span>No active campaigns to chart.</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <span class="feature-card-action d-flex align-items-center justify-content-between gap-2 mt-auto">
                    <span><?= htmlspecialchars($card['action']) ?></span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </span>
            </a>
        </div>
    <?php endforeach; ?>
</section>
