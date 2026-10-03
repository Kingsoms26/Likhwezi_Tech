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
<script>
    // set up every photo uploader on the page
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.gallery-uploader').forEach((root) => {
            const form = root.closest('form');
            const input = root.querySelector('.gallery-dropzone-input');
            const zone = root.querySelector('.gallery-dropzone');
            const previews = root.querySelector('.gallery-upload-previews');
            const error = root.querySelector('.gallery-upload-error');
            const submit = form.querySelector('[type="submit"]');
            const submitText = submit.textContent.trim();
            const required = root.dataset.required === '1';

            const maxBytes = Number(root.dataset.maxBytes);
            const postLimit = Number(root.dataset.postLimit);
            const maxFiles = Number(root.dataset.maxFiles) || 20;
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            const mb = (bytes) => (bytes / 1048576).toFixed(1).replace(/\.0$/, '') + ' MB';

            // the chosen photos, the file input is rebuilt from this list on every change
            let files = [];

            const showError = (message) => {
                error.textContent = message;
                error.hidden = !message;
            };

            // show the previews and update the submit button
            const render = () => {
                const transfer = new DataTransfer();
                files.forEach((file) => transfer.items.add(file));
                input.files = transfer.files;

                previews.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
                previews.replaceChildren(...files.map((file, index) => {
                    const item = document.createElement('li');
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.alt = '';
                    const name = document.createElement('span');
                    name.textContent = file.name;
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'gallery-upload-remove';
                    remove.setAttribute('aria-label', 'Remove ' + file.name);
                    remove.textContent = '×';
                    remove.addEventListener('click', () => {
                        files.splice(index, 1);
                        render();
                    });
                    item.append(img, name, remove);
                    return item;
                }));
                previews.hidden = files.length === 0;

                // check the number and size of the photos, leaving room for the other fields
                const total = files.reduce((sum, file) => sum + file.size, 0);
                let message = '';
                if (files.length > maxFiles) {
                    message = 'You can upload up to ' + maxFiles + ' photos at a time.';
                } else if (postLimit && total > postLimit * 0.95) {
                    message = 'These photos add up to ' + mb(total) + '. The limit per upload is ' + mb(postLimit) + ', so please choose fewer.';
                }
                showError(message);

                submit.disabled = message !== '' || (required && files.length === 0);
                if (required) {
                    submit.textContent = files.length > 1 ? 'Upload ' + files.length + ' photos' : 'Upload';
                }
            };

            // add photos, skipping any with the wrong type or size
            const add = (list) => {
                const skipped = [];
                [...list].forEach((file) => {
                    if (!allowed.includes(file.type)) skipped.push(file.name + ' is not a JPG, PNG or WEBP image');
                    else if (file.size > maxBytes) skipped.push(file.name + ' is larger than ' + mb(maxBytes));
                    else files.push(file);
                });
                render();
                if (skipped.length) showError('Skipped: ' + skipped.join('; ') + '.');
            };

            // choosing again adds to the photos already picked instead of replacing them
            input.addEventListener('change', () => add(input.files));

            // highlight while dragging over then add the dropped photos
            ['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (event) => {
                event.preventDefault();
                zone.classList.add('is-dragging');
            }));

            ['dragleave', 'drop'].forEach((type) => zone.addEventListener(type, (event) => {
                event.preventDefault();
                if (type === 'dragleave' && zone.contains(event.relatedTarget)) return;
                zone.classList.remove('is-dragging');
            }));

            zone.addEventListener('drop', (event) => add(event.dataTransfer.files));

            // clear the uploader
            root.galleryReset = () => {
                files = [];
                render();
                showError('');
            };

            // show that the form is sending
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.textContent = files.length ? 'Uploading...' : 'Saving...';
            });

            // keep the submit button's own text until photos change it
            if (!required) submit.textContent = submitText;
            render();
        });
    });
</script>
<?php endif; ?>
