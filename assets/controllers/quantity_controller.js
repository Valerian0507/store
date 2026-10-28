import { Controller } from '@hotwired/stimulus';

/*
 * Sélecteur de quantité − / + (fiche produit).
 * Reste entre min et max (max = stock disponible) ; le champ reste saisissable au clavier.
 */
export default class extends Controller {
    static targets = ['input', 'decrement', 'increment'];

    connect() {
        this.update();
    }

    decrement() {
        this.set(this.value - 1);
    }

    increment() {
        this.set(this.value + 1);
    }

    // Saisie au clavier : on corrige les valeurs hors limites
    clamp() {
        this.set(this.value);
    }

    get value() {
        return parseInt(this.inputTarget.value, 10) || this.min;
    }

    get min() {
        return parseInt(this.inputTarget.min, 10) || 1;
    }

    get max() {
        return parseInt(this.inputTarget.max, 10) || Infinity;
    }

    set(value) {
        this.inputTarget.value = Math.min(this.max, Math.max(this.min, value));
        this.update();
    }

    update() {
        this.decrementTarget.disabled = this.value <= this.min;
        this.incrementTarget.disabled = this.value >= this.max;
    }
}
