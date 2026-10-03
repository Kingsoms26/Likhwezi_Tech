<!-- dashboardTable.php wraps a dashboard table, the page passes in the rows as $tableContent -->
<div class="dashboard-table-wrap">
    <table class="dashboard-table">
        <?= $tableContent ?? '' ?>
    </table>
</div>
