import { Controller } from '@hotwired/stimulus';

/*
 * Prix saisi en centimes (admin produit) : affiche l'équivalent en euros, ex. "49900 = 499,00 €".
 */
export default class extends Controller {
    static targets = ['input', 'output'];

    connect() {
        this.update();
    }

    update() {
        const cents = parseInt(this.inputTarget.value, 10);

        this.outputTarget.textContent = Number.isInteger(cents) && cents >= 0
            ? `${cents} = ${(cents / 100).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`
            : '';
    }
}
