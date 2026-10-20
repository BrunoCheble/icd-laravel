{{-- Manual crop of the picture chosen in a file field (Cropper.js), in the announcements' format (16:10): a window to
     choose the part to show, with zoom and rotation; the cropped picture (JPEG, 1600 × 1000) replaces the chosen file
     before sending. PDFs go as they are.
     $input: the file field's id. Included once per field. --}}
@once
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <style>
        .crop-preview { display: none; align-items: center; gap: .75rem; margin-top: .6rem; padding: .6rem; border: 1px solid var(--bd-e5e7eb); border-radius: .8rem; background: var(--bg-f9fafb); }
        .crop-preview.is-on { display: flex; }
        .crop-preview img { width: 6.5rem; aspect-ratio: 16 / 10; object-fit: cover; border-radius: .55rem; background: var(--bg-fff); }
        .crop-preview span { flex: 1; min-width: 0; font-size: .82rem; color: var(--tx-4b5563); }
        .crop-preview button { flex: none; padding: .45rem .8rem; border: 1px solid var(--bd-d1d5db); border-radius: 999px; background: var(--bg-fff); color: var(--tx-111827); font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; }
        .crop-window { position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(15, 23, 42, .7); }
        .crop-window.is-open { display: flex; }
        .crop-box { display: flex; flex-direction: column; width: min(46rem, 100%); max-height: calc(100vh - 2rem); border-radius: 1.25rem; background: var(--bg-fff); overflow: hidden; box-shadow: 0 30px 80px rgba(0, 0, 0, .4); font-family: inherit; }
        .crop-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--bd-e5e7eb); }
        .crop-head strong { font-size: 1.05rem; color: var(--tx-111827); }
        .crop-head small { display: block; font-size: .8rem; font-weight: 500; color: var(--tx-6b7280); }
        .crop-stage { flex: 1; min-height: 16rem; height: 55vh; background: #111827; }
        .crop-stage img { display: block; max-width: 100%; }
        .crop-tools { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem 1.25rem; border-top: 1px solid var(--bd-e5e7eb); }
        .crop-moves { display: flex; flex-wrap: wrap; gap: .35rem; }
        .crop-format { font-size: .82rem; font-weight: 700; color: var(--tx-92400e); }
        .crop-tools button { min-height: 38px; padding: 0 .8rem; border: 1px solid var(--bd-d1d5db); border-radius: 999px; background: var(--bg-fff); color: var(--tx-374151); font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; }
        .crop-tools button.is-on { border-color: #f8a925; background: var(--bg-fef3e2); color: var(--tx-92400e); }
        .crop-foot { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .5rem; padding: .85rem 1.25rem; border-top: 1px solid var(--bd-e5e7eb); }
        .crop-foot button { min-height: 44px; padding: 0 1.1rem; border-radius: .75rem; font: inherit; font-weight: 800; cursor: pointer; }
        .crop-foot .is-ghost { border: 1px solid var(--bd-d1d5db); background: var(--bg-fff); color: var(--tx-374151); }
        .crop-foot .is-main { border: 0; background: #f8a925; color: #fff; }
        @media (max-width: 640px) {
            .crop-window { padding: 0; align-items: flex-end; }
            .crop-box { border-radius: 1.25rem 1.25rem 0 0; max-height: 96vh; }
            .crop-stage { height: 48vh; }
            .crop-foot button { flex: 1; }
        }
    </style>
    <script>
        // A crop window for a file field, in the announcements' format: zoom and rotation; "Use the crop" puts the cropped
        // JPEG in the field (and a preview under it).
        window.imageCropper = function (input) {
            const box = document.getElementById(input.id + '-crop');
            const stage = box.querySelector('.crop-stage img');
            const preview = document.getElementById(input.id + '-preview');
            let cropper = null;
            let original = null;
            const done = (file) => {
                const list = new DataTransfer();
                list.items.add(file);
                input.files = list.files;
                preview.querySelector('img').src = URL.createObjectURL(file);
                preview.querySelector('span').textContent = `${file.name} · ${Math.round(file.size / 1024)} KB`;
                preview.classList.add('is-on');
                close();
            };
            const close = () => { box.classList.remove('is-open'); cropper?.destroy(); cropper = null; document.body.style.overflow = ''; };
            const open = (file) => {
                original = file;
                stage.src = URL.createObjectURL(file);
                box.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                stage.onload = () => {
                    cropper?.destroy();
                    cropper = new Cropper(stage, { aspectRatio: {{ \App\Modules\Announcements\Models\Announcement::PICTURE_WIDTH }} / {{ \App\Modules\Announcements\Models\Announcement::PICTURE_HEIGHT }}, viewMode: 1, autoCropArea: 1, background: false, responsive: true });
                };
            };
            input.addEventListener('change', () => {
                const file = input.files[0];
                if (file && /^image\/(jpeg|png|gif|webp)$/.test(file.type)) open(file);
                else preview.classList.remove('is-on');
            });
            box.querySelector('[data-rotate="-90"]').addEventListener('click', () => cropper?.rotate(-90));
            box.querySelector('[data-rotate="90"]').addEventListener('click', () => cropper?.rotate(90));
            box.querySelector('[data-zoom="in"]').addEventListener('click', () => cropper?.zoom(0.1));
            box.querySelector('[data-zoom="out"]').addEventListener('click', () => cropper?.zoom(-0.1));
            box.querySelector('[data-crop="cancel"]').addEventListener('click', () => { input.value = ''; preview.classList.remove('is-on'); close(); });
            box.querySelector('[data-crop="use"]').addEventListener('click', () => {
                const canvas = cropper.getCroppedCanvas({ width: {{ \App\Modules\Announcements\Models\Announcement::PICTURE_WIDTH }}, height: {{ \App\Modules\Announcements\Models\Announcement::PICTURE_HEIGHT }}, fillColor: '#fff', imageSmoothingQuality: 'high' });
                canvas.toBlob((blob) => {
                    const name = (original.name.replace(/\.[^.]+$/, '') || 'imagem') + '.jpg';
                    done(new File([blob], name, { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.88);
            });
            preview.querySelector('button').addEventListener('click', () => original && open(original));
        };
    </script>
@endonce

<div class="crop-preview" id="{{ $input }}-preview">
    <img src="" alt="">
    <span></span>
    <button type="button">{{ __('Crop again') }}</button>
</div>
<div class="crop-window" id="{{ $input }}-crop" role="dialog" aria-modal="true" aria-label="{{ __('Crop the picture') }}">
    <div class="crop-box">
        <div class="crop-head">
            <div><strong>{{ __('Crop the picture') }}</strong><small>{{ __('Drag to choose the part to show; the corners change its size.') }}</small></div>
        </div>
        <div class="crop-stage"><img alt=""></div>
        <div class="crop-tools">
            <span class="crop-format"><i class="fa-solid fa-crop-simple"></i> {{ __('Announcement format') }} 16:10</span>
            <div class="crop-moves">
                <button type="button" data-zoom="out" aria-label="{{ __('Zoom out') }}"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                <button type="button" data-zoom="in" aria-label="{{ __('Zoom in') }}"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                <button type="button" data-rotate="-90" aria-label="{{ __('Rotate left') }}"><i class="fa-solid fa-rotate-left"></i></button>
                <button type="button" data-rotate="90" aria-label="{{ __('Rotate right') }}"><i class="fa-solid fa-rotate-right"></i></button>
            </div>
        </div>
        <div class="crop-foot">
            <button type="button" class="is-ghost" data-crop="cancel">{{ __('Cancel') }}</button>
            <button type="button" class="is-main" data-crop="use">{{ __('Use the crop') }}</button>
        </div>
    </div>
</div>
<script>imageCropper(document.getElementById(@js($input)));</script>
