import { Controller } from '@hotwired/stimulus';
import { showToast } from '../lib/toast.js';

/*
 * Formulaire "Ajouter au panier" : envoi en arrière-plan (fetch),
 * l'utilisateur reste sur la page et une notification s'affiche en haut à droite.
 * Sans JS, le formulaire est envoyé normalement (CartController renvoie sur la page d'origine).
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

    // Badge sur l'icône panier (_partials/_navbar.html.twig)
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
