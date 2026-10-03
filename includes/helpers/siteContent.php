<?php
// siteContent.php fetches the website content admins edit on staff/manageContent.php
// contains the contact details, cym settings, service cards and cym photos
// each lookup is cached for an hour and falls back to the built in content below when the database is down
require_once __DIR__ . '/cache.php';

// built in contact details and cym settings
const DEFAULT_SITE_SETTINGS = [
    'contactAddress'  => 'Office 47, No.8 Incubation Drive, Fourways, Gauteng',
    'contactPhone'    => '+27 11 464 5083',
    'contactEmail'    => 'info@likhwezitech.co.za',
    'cymSiteURL'      => 'https://cyberyoungminds.co.za',
    'cymParticipants' => '255',
];

// built in service cards
const DEFAULT_SERVICES = [
    ['icon' => 'bi-diagram-3', 'tagline' => "Tomorrow's Direction", 'name' => 'Enterprise Architecture', 'description' => 'We map how your systems, data and processes fit together, then design a target architecture that supports where the business is heading.', 'includes' => ['Business Architecture', 'Application Architecture', 'Data Architecture', 'Technology Architecture']],
    ['icon' => 'bi-compass', 'tagline' => 'The Game Plan', 'name' => 'Strategic Advisory', 'description' => 'We give unbiased, experienced advice on high-level decisions, helping you shape business and data strategies that deliver real results.', 'includes' => ['Business & Data Strategy', 'Capability Mapping', 'Business Analysis & Evaluation', 'Business Process Management', 'Business Modelling']],
    ['icon' => 'bi-database', 'tagline' => 'Drive Efficiency', 'name' => 'Data Management', 'description' => 'We put the governance, structures and controls in place to protect your data, keep it accurate and turn it into a business asset.', 'includes' => ['Data Governance', 'Data Quality', 'Data Modelling & Design', 'Data Warehousing & BI', 'Data Integration', 'Data Security', 'Master Data Management']],
    ['icon' => 'bi-clipboard-check', 'tagline' => '20/20 Sight', 'name' => 'Data Testing', 'description' => 'We validate, verify and qualify your data so every report, system and decision is built on information that is accurate and fit for purpose.', 'includes' => ['Test Planning & Strategy', 'ETL & Integration Testing', 'Database Testing', 'Performance & Security Testing', 'Data Model Validation', 'Report Testing']],
    ['icon' => 'bi-rocket-takeoff', 'tagline' => 'Span the Enterprise', 'name' => 'Solution Delivery', 'description' => 'We plan, manage and deliver change from first design to final rollout, making sure your organisation actually realises the benefits.', 'includes' => ['Programme & Project Management', 'Demand Planning & Prioritisation', 'Stakeholder Management', 'Solution Design & Implementation']],
];

// built in cym photos
const DEFAULT_CYM_PHOTOS = [
    ['src' => 'assets/images/about-img/school-footage.webp', 'alt' => 'Cyber Young Minds school session'],
    ['src' => 'assets/images/about-img/school-footage2.webp', 'alt' => 'Cyber Young Minds school session'],
];

const SITE_CONTENT_CACHE_SECONDS = 3600;

// every site setting, using the built in value for any that are missing
function siteSettings(): array {
    $saved = cached('site_settings', SITE_CONTENT_CACHE_SECONDS, function () {
        $conn = db();
        $result = $conn ? $conn->query("SELECT settingKey, value FROM SiteSetting") : false;
        return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'value', 'settingKey') : null;
    }) ?? [];

    return array_merge(DEFAULT_SITE_SETTINGS, array_filter($saved, fn ($value) => $value !== ''));
}

// one site setting
function siteSetting(string $key): string {
    return siteSettings()[$key] ?? '';
}

// turn a phone number into a tel link
function phoneHref(string $phone): string {
    return 'tel:' . (str_starts_with(trim($phone), '+') ? '+' : '') . preg_replace('/\D/', '', $phone);
}

// active service cards in display order
function siteServices(): array {
    $services = cached('site_services', SITE_CONTENT_CACHE_SECONDS, function () {
        $conn = db();
        $result = $conn ? $conn->query(
            "SELECT name, tagline, icon, description, includes FROM Service WHERE isActive = TRUE ORDER BY sortOrder, name"
        ) : false;
        if (!$result) {
            return null;
        }

        return array_map(function (array $row) {
            // split the includes into a list, one per line
            $row['includes'] = array_values(array_filter(array_map('trim', explode("\n", $row['includes']))));
            return $row;
        }, $result->fetch_all(MYSQLI_ASSOC));
    });

    return $services ?: DEFAULT_SERVICES;
}

// photos for the cym page carousel in display order
function cymPhotos(): array {
    $photos = cached('cym_photos', SITE_CONTENT_CACHE_SECONDS, function () {
        $conn = db();
        $result = $conn ? $conn->query("SELECT src, altText AS alt FROM CymPhoto ORDER BY sortOrder, photoID") : false;
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : null;
    });

    return $photos ?: DEFAULT_CYM_PHOTOS;
}
