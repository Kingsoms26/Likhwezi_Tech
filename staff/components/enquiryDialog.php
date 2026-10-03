<!-- enquiryDialog.php shows the full details of one enquiry
 the script fills it in when a view button in components/enquiryTable.php is clicked
-->
<dialog class="dashboard-dialog enquiry-dialog" id="enquiry-dialog" aria-labelledby="enquiry-dialog-title">
    <form method="dialog" class="dashboard-form">
        <div class="dialog-header">
            <h2 id="enquiry-dialog-title">Enquiry</h2>
            <button type="submit" class="popover-icon-button" aria-label="Close">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/>
                </svg>
            </button>
        </div>

        <div class="dialog-body">
            <dl class="detail-list">
                <dt>Name</dt>         <dd data-field="name"></dd>
                <dt>Organisation</dt> <dd data-field="companyName"></dd>
                <dt>Email</dt>        <dd><a data-field="email"></a></dd>
                <dt>Phone</dt>        <dd><a data-field="phoneNumber"></a></dd>
                <dt>Meeting</dt>      <dd data-field="meetingType"></dd>
                <dt>Received</dt>     <dd data-field="received"></dd>
                <dt>Status</dt>       <dd data-field="status"></dd>
                <dt>Handled by</dt>   <dd data-field="handledBy"></dd>
            </dl>
            <p class="enquiry-message" data-field="description"></p>
        </div>

        <div class="dialog-footer">
            <button type="submit" class="panel-button">Close</button>
        </div>
    </form>
</dialog>

<script src="assets/js/enquiryDialog.js?v=<?= filemtime(__DIR__ . '/../assets/js/enquiryDialog.js') ?>"></script>
