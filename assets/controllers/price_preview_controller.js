import { Controller } from '@hotwired/stimulus';

/*
 * Price typed in cents (admin product): shows the amount in euros, e.g. "49900 = 499,00 €".
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
