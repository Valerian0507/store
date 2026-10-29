import { Controller } from '@hotwired/stimulus';
import { showToast } from '../lib/toast.js';

/*
 * "Ajouter au panier" form: sent in the background (fetch),
 * the user stays on the page and a notification shows up at the top right.
 * Without JS, the form is submitted normally (CartController redirects back to the page).
 */
export default class extends Controller {
    async submit(event) {
        event.preventDefault();

        const form = this.element;
        const button = form.querySelector('[type="submit"]');
        if (button) button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            showToast(data.type, data.message);
            if (Number.isInteger(data.cartCount)) this.updateBadge(data.cartCount);
        } catch (error) {
            showToast('danger', 'Impossible d\'ajouter le produit, veuillez réessayer.');
        } finally {
            if (button) button.disabled = false;
        }
    }

    // Badge on the cart icon (_partials/_navbar.html.twig)
    updateBadge(count) {
        document.querySelectorAll('[data-cart-count]').forEach((badge) => {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count === 0;
        });
        document.querySelectorAll('[data-cart-link]').forEach((link) => {
            link.setAttribute('aria-label', count > 0 ? `Panier (${count} article${count > 1 ? 's' : ''})` : 'Panier');
        });
    }
}
