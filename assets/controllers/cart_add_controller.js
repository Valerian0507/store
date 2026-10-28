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
        } catch (error) {
            showToast('danger', 'Impossible d\'ajouter le produit, veuillez réessayer.');
        } finally {
            if (button) button.disabled = false;
        }
    }
}
