
/* ==========================================
   Library Notification Toast
   Preview once per notification per tab
   ========================================== */

document.addEventListener("DOMContentLoaded", () => {
    const toast = document.getElementById(
        "libraryNotificationToast"
    );

    if (!toast) return;

    const closeButton = document.getElementById(
        "libraryNotificationToastClose"
    );

    const notificationId = toast.dataset.notificationId;

    if (!notificationId) return;

    const storageKey = "library-toast-shown:" + notificationId;

    let alreadyShown = false;

    try {
        alreadyShown = sessionStorage.getItem(storageKey) === "1";
    } catch (error) {
        // Storage can be unavailable in restricted browsers.
    }

    if (alreadyShown) return;

    try {
        sessionStorage.setItem(storageKey, "1");
    } catch (error) {
        // Toast can still appear when storage is restricted.
    }

    toast.hidden = false;

    let dismissing = false;
    let removalTimer;

    const dismissToast = () => {
        if (dismissing) return;

        dismissing = true;
        toast.classList.add("is-hiding");

        removalTimer = window.setTimeout(() => {
            toast.remove();
        }, 230);
    };

    const hideTimer = window.setTimeout(
        dismissToast,
        8000
    );

    if (closeButton) {
        closeButton.addEventListener("click", () => {
            window.clearTimeout(hideTimer);
            dismissToast();
        });
    }
});
