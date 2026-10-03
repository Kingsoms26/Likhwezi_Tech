<?php
    // galleryUploader.php is the multi photo upload box on manageEvents.php and manageCampaigns.php
    // drag photos in or choose them, see previews and remove any before sending
    // needs $uploaderID, $uploaderLabel, $uploaderHint and $uploaderRequired are optional
    // when required the submit button stays disabled until a photo is chosen
    // the form must use multipart/form-data and galleryReset() clears the uploader

    $uploaderLabel = $uploaderLabel ?? 'Photos';
    $uploaderHint = $uploaderHint ?? '';
    $uploaderRequired = $uploaderRequired ?? false;
?>

<div class="form-field gallery-uploader" data-max-bytes="<?= GALLERY_PHOTO_MAX_BYTES ?>"
     data-post-limit="<?= galleryPostLimitBytes() ?>" data-max-files="<?= (int) ini_get('max_file_uploads') ?>"
     <?= $uploaderRequired ? 'data-required="1"' : '' ?>>
    <label for="<?= htmlspecialchars($uploaderID) ?>">
        <?= htmlspecialchars($uploaderLabel) ?>
        <?php if ($uploaderHint !== '') : ?><span class="field-hint"><?= htmlspecialchars($uploaderHint) ?></span><?php endif; ?>
    </label>

    <!-- drop zone -->
    <div class="gallery-dropzone">
        <input type="file" id="<?= htmlspecialchars($uploaderID) ?>" name="photos[]" class="gallery-dropzone-input"
               accept="image/jpeg,image/png,image/webp" multiple>
        <span class="photo-dropzone-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="26" height="26" focusable="false">
                <path fill="currentColor" d="M5 20h14v-2H5v2Zm7-16-6 6h4v6h4v-6h4l-6-6Z"/>
            </svg>
        </span>
        <p>Drag photos here or</p>
        <label for="<?= htmlspecialchars($uploaderID) ?>" class="photo-dropzone-button">Choose photos</label>
        <p class="field-hint">JPG, PNG or WEBP, max <?= GALLERY_PHOTO_MAX_BYTES / 1048576 ?> MB each</p>
    </div>

    <!-- previews and error message -->
    <ul class="gallery-upload-previews" hidden></ul>
    <p class="photo-dropzone-error gallery-upload-error" role="alert" hidden></p>
</div>

<!-- clear the settings so they do not carry over to the next uploader -->
<?php unset($uploaderLabel, $uploaderHint, $uploaderRequired); ?>

<!-- the script is only added once per page -->
<?php if (empty($galleryUploaderScript)) : $galleryUploaderScript = true; ?>
<script src="assets/js/galleryUploader.js?v=<?= filemtime(__DIR__ . '/../assets/js/galleryUploader.js') ?>"></script>
<?php endif; ?>
