@props([
    'id' => 'file-upload',
    'name' => 'file',
    'label' => 'Choose file',
    'variant' => 'import',
    'accept' => '',
    'maxBytes' => 0,
    'hint' => '',
    'required' => false,
    'invalid' => false,
    'describedby' => '',
    'submitLabel' => null,
])

<div
    {{ $attributes->class([
        'fu-component',
        'fu-' . $variant
    ]) }}
    data-file-upload
    data-max-bytes="{{ $maxBytes }}"
    data-required="{{ $required ? 'true' : 'false' }}"
>

    <!-- Native input retained for Laravel submission -->

    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        class="fu-input form-control @if($invalid) is-invalid @endif"
        aria-label="{{ $label }}"
        aria-describedby="{{ $id }}-hint {{ $id }}-error {{ $describedby }}"
        @required($required)
        @if($invalid) aria-invalid="true" @endif
        data-fu-input
    >

    <div class="fu-enhanced" hidden>

        <!-- Dropzone -->

        <button
            type="button"
            class="fu-dropzone"
            data-fu-browse
            aria-label="{{ $label }}"
            aria-describedby="{{ $id }}-hint"
        >
            <span class="fu-upload-icon">
                <i class="bi bi-cloud-arrow-up"
                   aria-hidden="true"></i>
            </span>

            <span class="fu-dropzone-copy">

                <span class="fu-dropzone-title">
                    Drop file here or
                    <strong>browse</strong>
                </span>

                <span class="fu-hint" id="{{ $id }}-hint">
                    {{ $hint }}
                </span>

            </span>
        </button>

        <!-- Selected file and submission -->

        <div class="fu-side">

            <div class="fu-preview">

                <span class="fu-file-icon">
                    <i class="bi bi-file-earmark"
                       data-fu-icon
                       aria-hidden="true"></i>

                    <img
                        alt=""
                        data-fu-image
                        hidden
                    >
                </span>

                <div class="fu-file-details"
                     aria-live="polite">

                    <strong
                        class="fu-filename"
                        data-fu-filename
                    >
                        No file selected
                    </strong>

                    <small
                        class="fu-file-meta"
                        data-fu-meta
                    >
                        Select a file to continue
                    </small>

                </div>

                <button
                    type="button"
                    class="fu-remove"
                    data-fu-remove
                    aria-label="Remove selected file"
                    title="Remove selected file"
                    hidden
                >
                    <i class="bi bi-x-lg"
                       aria-hidden="true"></i>
                </button>

            </div>

            @if($submitLabel)
                <button
                    type="submit"
                    class="btn btn-primary fu-submit"
                    data-fu-submit
                >
                    <i class="bi bi-upload"
                       aria-hidden="true"></i>

                    <span data-fu-submit-label>
                        {{ $submitLabel }}
                    </span>
                </button>
            @endif

        </div>

    </div>

    {{-- Fallback when JavaScript is unavailable --}}

    @if($submitLabel)
        <button
            type="submit"
            class="btn btn-primary mt-2"
            data-fu-fallback
        >
            {{ $submitLabel }}
        </button>
    @endif

    <p
        id="{{ $id }}-error"
        class="fu-error"
        role="alert"
        data-fu-error
        hidden
    ></p>

</div>