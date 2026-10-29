import { Controller } from '@hotwired/stimulus';

/*
 * Quantity selector − / + (product page).
 * Stays between min and max (max = available stock); the field can still be typed in.
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

    // Typed value: out-of-range values are corrected
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
