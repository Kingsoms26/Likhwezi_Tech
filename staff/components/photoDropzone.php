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

<script>
    // set up every drop zone on the page once
    document.querySelectorAll('.photo-dropzone:not([data-ready])').forEach((zone) => {
        zone.dataset.ready = '1';

        const input = zone.querySelector('.photo-dropzone-input');
        const empty = zone.querySelector('.photo-dropzone-empty');
        const preview = zone.querySelector('.photo-dropzone-preview');
        const image = preview.querySelector('img');
        const filename = preview.querySelector('.photo-dropzone-filename');
        const error = zone.parentElement.querySelector('.photo-dropzone-error');
        const maxBytes = Number(zone.dataset.maxBytes);
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];

        const showError = (message) => {
            error.textContent = message;
            error.hidden = !message;
        };

        // clear the chosen image
        const reset = () => {
            input.value = '';
            if (image.src.startsWith('blob:')) URL.revokeObjectURL(image.src);
            image.removeAttribute('src');
            preview.hidden = true;
            empty.hidden = false;
            zone.classList.remove('has-image');
        };

        // check the image type and size then show a preview
        const show = (file) => {
            if (!file) return reset();

            if (!allowed.includes(file.type)) {
                reset();
                return showError('Please choose a JPG, PNG or WEBP image.');
            }
            if (file.size > maxBytes) {
                reset();
                return showError('That image is too large. The limit is ' + (maxBytes / 1048576) + ' MB.');
            }

            showError('');
            if (image.src.startsWith('blob:')) URL.revokeObjectURL(image.src);
            image.src = URL.createObjectURL(file);
            filename.textContent = file.name;
            empty.hidden = true;
            preview.hidden = false;
            zone.classList.add('has-image');
        };

        input.addEventListener('change', () => show(input.files[0]));

        // remove button
        zone.querySelector('.photo-dropzone-remove').addEventListener('click', () => {
            reset();
            showError('');
        });

        // highlight while dragging over then hand the file to the input
        ['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.add('is-dragging');
        }));

        ['dragleave', 'drop'].forEach((type) => zone.addEventListener(type, (event) => {
            event.preventDefault();
            if (type === 'dragleave' && zone.contains(event.relatedTarget)) return;
            zone.classList.remove('is-dragging');
        }));

        zone.addEventListener('drop', (event) => {
            const file = event.dataTransfer.files[0];
            if (!file) return;

            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            show(file);
        });
    });
</script>
