import { Controller } from '@hotwired/stimulus';

/*
 * Bouton "œil" d'un champ mot de passe : affiche / masque la saisie.
 */
export default class extends Controller {
    static targets = ['input', 'button', 'icon'];

    toggle() {
        const visible = this.inputTarget.type === 'password';

        this.inputTarget.type = visible ? 'text' : 'password';
        this.buttonTarget.setAttribute('aria-pressed', String(visible));
        this.buttonTarget.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        this.iconTarget.classList.toggle('fa-eye', !visible);
        this.iconTarget.classList.toggle('fa-eye-slash', visible);
        this.inputTarget.focus();
    }
}
