/*
 * Notifications at the top right (.flash-container, see _partials/_flash.html.twig).
 * Server flash messages and the ones added from JS disappear after 5 seconds.
 */
const DELAY = 5000;

export function autoHide(alert) {
    // Bar under the message: empties during the delay before it disappears
    const progress = document.createElement('span');
    progress.className = 'flash-progress';
    progress.style.animationDuration = `${DELAY}ms`;
    alert.append(progress);

    window.setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        window.setTimeout(() => alert.remove(), 500);
    }, DELAY);
}

export function showToast(type, message) {
    const container = document.querySelector('.flash-container');
    if (!container) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} js-flash`;
    alert.setAttribute('role', type === 'success' ? 'status' : 'alert');
    alert.textContent = message;

    container.append(alert);
    autoHide(alert);
}
