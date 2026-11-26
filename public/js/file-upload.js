/* ==================================================
   UBNHS LMS
   Unified File Upload Controller
   ================================================== */

(() => {
    "use strict";

    function formatBytes(bytes) {
        if (bytes < 1024) return `${bytes} B`;

        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    }

    function acceptsFile(file, accept) {
        if (!accept) return true;

        const rules = accept
            .toLowerCase()
            .split(",")
            .map(rule => rule.trim())
            .filter(Boolean);

        if (!rules.length) return true;

        const name = file.name.toLowerCase();
        const type = file.type.toLowerCase();

        return rules.some(rule => {
            if (rule.startsWith(".")) {
                return name.endsWith(rule);
            }

            if (rule.endsWith("/*")) {
                return type.startsWith(rule.slice(0, -1));
            }

            return type === rule;
        });
    }

    function initUpload(component) {
        if (component.dataset.initialized === "true") {
            return;
        }

        const input = component.querySelector("[data-fu-input]");
        const enhanced = component.querySelector(".fu-enhanced");
        const browse = component.querySelector("[data-fu-browse]");
        const remove = component.querySelector("[data-fu-remove]");
        const filename = component.querySelector("[data-fu-filename]");
        const meta = component.querySelector("[data-fu-meta]");
        const icon = component.querySelector("[data-fu-icon]");
        const image = component.querySelector("[data-fu-image]");
        const error = component.querySelector("[data-fu-error]");
        const submit = component.querySelector("[data-fu-submit]");
        const submitLabel = component.querySelector("[data-fu-submit-label]");
        const fallback = component.querySelector("[data-fu-fallback]");

        if (!input || !enhanced || !browse || !filename || !meta) {
            return;
        }

        const form = input.form;

        const maxBytes = Number(
            component.dataset.maxBytes || 0
        );

        const required =
            component.dataset.required === "true";

        const originalLabel =
            submitLabel?.textContent.trim() || "Upload";

        const originalInvalid =
            input.getAttribute("aria-invalid") === "true";

        let previewUrl = null;
        let submitting = false;
        let dragDepth = 0;
        let serverInvalid = originalInvalid;

        /* Validation feedback */

        function showError(message = "") {
            component.classList.toggle(
                "has-error",
                Boolean(message) || serverInvalid
            );

            input.setCustomValidity(message);

            if (message || serverInvalid) {
                input.setAttribute("aria-invalid", "true");
            } else {
                input.removeAttribute("aria-invalid");
            }

            if (error) {
                error.textContent = message;
                error.hidden = !message;
            }
        }

        function validateFile(file) {
            if (!acceptsFile(file, input.accept)) {
                return "Choose a supported file format.";
            }

            if (maxBytes > 0 && file.size > maxBytes) {
                return `File must be ${formatBytes(maxBytes)} or smaller.`;
            }

            if (file.size === 0) {
                return "This file is empty. Choose another file.";
            }

            return "";
        }

        /* Image preview */

        function clearImagePreview() {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            if (image) {
                image.hidden = true;
                image.removeAttribute("src");
            }

            if (icon) icon.hidden = false;
        }

        function updateImagePreview(file) {
            clearImagePreview();

            if (
                !image ||
                !file.type.startsWith("image/") ||
                !component.classList.contains("fu-avatar")
            ) {
                return;
            }

            previewUrl = URL.createObjectURL(file);

            image.src = previewUrl;
            image.hidden = false;

            if (icon) icon.hidden = true;
        }

        /* Submit button state */

        function syncSubmit(valid) {
            if (!submit) return;

            submit.disabled =
                submitting ||
                !valid ||
                (required && !input.files.length);
        }

        /* Render selected file */

        function renderFile() {
            clearImagePreview();

            const file = input.files[0];

            if (!file) {
                component.classList.remove("has-file");

                filename.textContent = "No file selected";
                filename.removeAttribute("title");

                meta.textContent = "Select a file to continue";

                if (icon) {
                    icon.className = "bi bi-file-earmark";
                }

                if (remove) remove.hidden = true;

                showError("");
                syncSubmit(true);

                return;
            }

            const message = validateFile(file);
            const valid = !message;

            showError(message);

            component.classList.toggle("has-file", valid);

            filename.textContent = file.name;
            filename.title = file.name;

            meta.textContent = valid
                ? `${formatBytes(file.size)} · Ready to upload`
                : `${formatBytes(file.size)} · Invalid file`;

            if (remove) remove.hidden = false;

            if (icon) {
                const extension = file.name
                    .split(".")
                    .pop()
                    .toLowerCase();

                icon.className =
                    ["csv", "xls", "xlsx"].includes(extension)
                        ? "bi bi-file-earmark-spreadsheet"
                        : "bi bi-file-earmark";
            }

            if (valid) updateImagePreview(file);

            syncSubmit(valid);
        }

        /* Browse files */

        browse.addEventListener("click", () => {
            if (!submitting && !input.disabled) {
                input.click();
            }
        });

        input.addEventListener("change", () => {
            serverInvalid = false;
            input.classList.remove("is-invalid");
            renderFile();
        });

        /* Remove selected file */

        remove?.addEventListener("click", () => {
            if (submitting) return;

            input.value = "";
            serverInvalid = false;
            input.classList.remove("is-invalid");

            renderFile();
            browse.focus();
        });

        /* Drag and drop */

        browse.addEventListener("dragenter", event => {
            event.preventDefault();

            if (submitting || input.disabled) return;

            dragDepth++;
            browse.classList.add("is-dragging");
        });

        browse.addEventListener("dragover", event => {
            event.preventDefault();

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect =
                    submitting ? "none" : "copy";
            }
        });

        browse.addEventListener("dragleave", event => {
            event.preventDefault();

            dragDepth = Math.max(0, dragDepth - 1);

            if (dragDepth === 0) {
                browse.classList.remove("is-dragging");
            }
        });

        browse.addEventListener("drop", event => {
            event.preventDefault();

            dragDepth = 0;
            browse.classList.remove("is-dragging");

            if (submitting || input.disabled) return;

            const files = event.dataTransfer?.files;

            if (!files?.length) return;

            if (files.length !== 1) {
                input.value = "";
                serverInvalid = false;

                renderFile();
                showError("Select only one file at a time.");
                syncSubmit(false);

                return;
            }

            try {
                const transfer = new DataTransfer();
                transfer.items.add(files[0]);
                input.files = transfer.files;

                serverInvalid = false;
                input.classList.remove("is-invalid");

                input.dispatchEvent(
                    new Event("change", { bubbles: true })
                );

            } catch {
                input.value = "";
                renderFile();

                showError(
                    "Drag and drop is unavailable. Browse for your file instead."
                );

                syncSubmit(false);
            }
        });

        /* Native form validation */

        input.addEventListener("invalid", event => {
            event.preventDefault();

            const message =
                input.validationMessage ||
                "Please select a valid file.";

            showError(message);
            browse.focus();
        });

        /* Actual form submission */

        form?.addEventListener("submit", event => {
            if (event.defaultPrevented) return;

            const file = input.files[0];

            const message = file
                ? validateFile(file)
                : required
                    ? "Please select a file."
                    : "";

            if (message) {
                event.preventDefault();

                showError(message);
                browse.focus();
                syncSubmit(false);

                return;
            }

            if (!submit) return;

            if (submitting) {
                event.preventDefault();
                return;
            }

            submitting = true;

            submit.disabled = true;
            browse.disabled = true;

            if (remove) remove.disabled = true;

            if (submitLabel) {
                submitLabel.textContent = "Uploading…";
            }

            form.setAttribute("aria-busy", "true");
        });

        /* Restore state after browser navigation */

        window.addEventListener("pageshow", () => {
            submitting = false;

            browse.disabled = false;

            if (remove) remove.disabled = false;

            if (submitLabel) {
                submitLabel.textContent = originalLabel;
            }

            form?.removeAttribute("aria-busy");

            renderFile();
        });

        form?.addEventListener("reset", () => {
            window.setTimeout(() => {
                serverInvalid = false;
                renderFile();
            }, 0);
        });

        /* Enable enhanced interface */

        component.dataset.initialized = "true";

        enhanced.hidden = false;

        if (fallback) fallback.hidden = true;

        input.tabIndex = -1;

        component.classList.add("is-enhanced");

        renderFile();
    }

    function initialize() {
        document
            .querySelectorAll("[data-file-upload]")
            .forEach(initUpload);
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initialize
        );
    } else {
        initialize();
    }
})();