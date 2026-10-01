<aside class="dashboard-sidebar">
    <nav aria-label="Dashboard navigation">
        <a class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="adminDashboard.php">
            Dashboard
        </a>

        <div class="sidebar-section">
            <a class="sidebar-link <?= $activePage === 'enquiries' ? 'active' : '' ?>" href="#">
                Enquiries
            </a>
            <a class="sidebar-link <?= $activePage === 'registrations' ? 'active' : '' ?>" href="#">
                Registrations
            </a>
            <a class="sidebar-link <?= $activePage === 'sponsors' ? 'active' : '' ?>" href="#">
                Sponsors
            </a>
        </div>

        <div class="sidebar-section">
            <a class="sidebar-link <?= $activePage === 'campaigns' ? 'active' : '' ?>" href="#">
                Campaigns
            </a>
            <a class="sidebar-link <?= $activePage === 'gallery' ? 'active' : '' ?>" href="#">
                Gallery
            </a>
            <a class="sidebar-link <?= $activePage === 'partners' ? 'active' : '' ?>" href="#">
                Partners
            </a>
        </div>

        <div class="sidebar-section">
            <a class="sidebar-link <?= $activePage === 'accounts' ? 'active' : '' ?>" href="#">
                Accounts
            </a>
            <a class="sidebar-link <?= $activePage === 'archive' ? 'active' : '' ?>" href="#">
                Archive
            </a>
        </div>
    </nav>
</aside>
