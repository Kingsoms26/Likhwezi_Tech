PROJECT STRUCTURE
-----------------

    adminDashboard.php
        Admin dashboard page and Admin-specific content.

    marketingDashboard.php
        Marketing dashboard template.

    customerServiceDashboard.php
        Customer Service dashboard template.

    index.php
        Standalone preview entry point.

    components/
        dashboard.php
            Shared dashboard shell.

        dashboardHeader.php
            Shared header.

        dashboardSidebar.php
            Shared sidebar/navigation.

        dashboardCard.php
            Reusable statistic card.

        dashboardPanel.php
            Reusable large panel.

        dashboardTable.php
            Reusable table.

        dashboardProgress.php
            Reusable progress bar.

    tools/
        dashboardData.php
            Shared dashboard data/functions.

    css/
        dashboard.css
            All shared dashboard styling.

    images/
        logo.jpg
            Dashboard logo.


DASHBOARD SHELL
---------------

components/dashboard.php is the shared base template.

It provides:

    Header
    Sidebar
    Main content area

SESSION
-------

Start each dashboard with:

    session_start();

The framework uses:

    $_SESSION['username']
        Name, surname or username displayed in the top-right.

    $_SESSION['role']
        The current user's role/state.

ROLE-SPECIFIC CSS
-----------------

Each dashboard identifies itself in its own HTML.

Admin:

    <div class="admin-dashboard">

Marketing:

    <div class="marketing-dashboard">

Customer Service:

    <div class="customer-service-dashboard">

Do not dynamically build these class names from the session.

Shared card styling:

    .dashboard-card {
        background: #ffffff;
    }

Admin-only change:

    .admin-dashboard .dashboard-card {
        background: #eef3ff;
    }

Marketing-only change:

    .marketing-dashboard .dashboard-card {
        background: #eef8f2;
    }

Customer Service-only change:

    .customer-service-dashboard .dashboard-card {
        background: #fff5e8;
    }

Only the targeted dashboard is changed.


SEMANTIC CLASS NAMES
--------------------

Use names that describe what a dashboard item represents.

Good:

    registration-card
    enquiries-card
    campaigns-card
    donations-card

    enquiries-panel
    registration-interest-panel
    campaigns-panel

Try Avoid:

    card-1
    card-2
    blue-box
    big-panel

For example:

    <article class="dashboard-card enquiries-card">

means:

    dashboard-card
        Shared card structure.

    enquiries-card
        The business purpose of this card.


COMPONENTS
----------

dashboardCard.php
    Reusable statistic card.

    Expects:
        $cardTitle
        $cardValue
        $cardMeta
        $cardClass

    It renders HTML only. It does not query the database.

dashboardPanel.php
    Reusable large content panel.

dashboardTable.php
    Shared table structure.

dashboardProgress.php
    Reusable progress bar.

dashboardHeader.php
    Shared header. Displays $_SESSION['username'].

dashboardSidebar.php
    Shared navigation. Uses $activePage.

dashboard.php
    Shared dashboard shell.

Only create or change shared components when the change should apply to
multiple dashboards.


DATABASE / DATA FLOW
--------------------

Responsibilities:

    dbConnection.php
        Creates/uses the database connection.

    dashboardData.php
        Gets reusable dashboard data.

    Role dashboard page
        Decides what information to display.

    Components
        Provide reusable HTML structure.

    dashboard.css
        Controls appearance.


SHARED DATABASE DATA
--------------------

If two dashboards need the same information, use one shared function instead
of copying the SQL into both pages. This is the purpose of dashboardData.php

Example:

    getDashboardStats($conn)

could return:

    [
        'activeEnquiries' => ...,
        'activeCampaigns' => ...,
        'fundsRaised' => ...
    ]

Both dashboards can then use:

    $stats['activeEnquiries']
    $stats['activeCampaigns']
    $stats['fundsRaised']

The data helper should return data, not HTML.


ROLE-SPECIFIC DATA
------------------

If only one role needs specialised data, use a role-specific helper.

Examples:

    tools/marketingData.php
    tools/customerServiceData.php

Do not put every query for the whole project into dashboardData.php.


CSS GUIDELINES
--------------

Shared styles use generic component names:

    .dashboard-card
    .dashboard-panel
    .dashboard-table
    .progress-track
    .progress-fill

Business-specific styles use semantic names:

    .registration-card
    .campaigns-panel
    .enquiries-panel

Role-specific styles are scoped:

    .admin-dashboard ...
    .marketing-dashboard ...
    .customer-service-dashboard ...

Only override the properties that need to change.

Do not copy an entire shared rule into a role-specific selector.

Use comments to explain sections, purpose and non-obvious decisions.
Do not comment every CSS property.

GENERAL RULE
------------

Pages own content.

Tools own data.

Components own reusable structure.
