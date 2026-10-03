<?php
    // photoDropzone.php is the image upload box, drag an image in or pick one with the button
    // needs $dropzoneID, $dropzoneName and $dropzoneLabel
    // $dropzoneHint, $dropzoneClass and $dropzoneMaxMB are optional

    $dropzoneMaxMB = $dropzoneMaxMB ?? 2;
    $dropzoneHint = $dropzoneHint ?? '(optional)';
    $dropzoneClass = $dropzoneClass ?? '';
?>

<div class="form-field">
    <label for="<?= htmlspecialchars($dropzoneID) ?>"><?= htmlspecialchars($dropzoneLabel) ?> <span class="field-hint"><?= htmlspecialchars($dropzoneHint) ?></span></label>

    <div class="photo-dropzone <?= htmlspecialchars($dropzoneClass) ?>" data-max-bytes="<?= $dropzoneMaxMB * 1024 * 1024 ?>">
        <input type="file" class="photo-dropzone-input" id="<?= htmlspecialchars($dropzoneID) ?>"
               name="<?= htmlspecialchars($dropzoneName) ?>" accept="image/jpeg,image/png,image/webp">

        <!-- shown until an image is chosen -->
        <div class="photo-dropzone-empty">
            <span class="photo-dropzone-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="26" height="26" focusable="false">
                    <path fill="currentColor" d="M5 20h14v-2H5v2Zm7-16-6 6h4v6h4v-6h4l-6-6Z"/>
                </svg>
            </span>
            <p>Drag image here to upload or</p>
            <label for="<?= htmlspecialchars($dropzoneID) ?>" class="photo-dropzone-button">Choose image</label>
            <p class="field-hint">JPG, PNG or WEBP, max <?= $dropzoneMaxMB ?> MB</p>
        </div>

        <!-- shown once an image is chosen -->
        <div class="photo-dropzone-preview" hidden>
            <img src="" alt="Preview of the chosen image">
            <p class="photo-dropzone-filename"></p>
            <div class="photo-dropzone-actions">
                <label for="<?= htmlspecialchars($dropzoneID) ?>" class="photo-dropzone-button">Change</label>
                <button type="button" class="photo-dropzone-remove">Remove</button>
            </div>
        </div>
    </div>

    <!-- error message -->
    <p class="photo-dropzone-error" role="alert" hidden></p>
</div>

<script src="assets/js/photoDropzone.js?v=<?= filemtime(__DIR__ . '/../assets/js/photoDropzone.js') ?>"></script>
