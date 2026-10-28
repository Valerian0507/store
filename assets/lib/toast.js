/*
 * Notifications en haut à droite (.flash-container, cf. _partials/_flash.html.twig).
 * Les messages flash du serveur et ceux ajoutés en JS disparaissent après 5 secondes.
 */
const DELAY = 5000;

export function autoHide(alert) {
    // Barre sous le message : se vide pendant le délai avant disparition
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
