import { Controller } from '@hotwired/stimulus';
import { calculateVolume, calculateBudget } from '../lib/pool_tools.js';

export default class extends Controller {
    static targets = ['form', 'dimensions', 'diameter', 'result', 'error', 'litres', 'volume', 'filling', 'care'];

    connect() { this.changeShape(); }

    changeShape() {
        const round = this.formTarget.elements.shape.value === 'round';
        this.dimensionsTarget.hidden = round;
        this.diameterTarget.hidden = !round;
        this.dimensionsTarget.querySelectorAll('input').forEach(input => { input.disabled = round; });
        this.diameterTarget.querySelectorAll('input').forEach(input => { input.disabled = !round; });
        this.resultTarget.hidden = true;
    }

    invalidate() { this.resultTarget.hidden = true; this.errorTarget.textContent = ''; }

    calculate(event) {
        event.preventDefault();
        this.resultTarget.hidden = true;
        this.errorTarget.textContent = '';
        if (!this.formTarget.reportValidity()) return;
        const data = Object.fromEntries(new FormData(this.formTarget));
        try {
            const result = calculateVolume(data);
            const budget = calculateBudget(result.cubicMetres, data.waterPrice, data.monthlyBudget, data.months);
            const format = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
            const money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
            this.litresTarget.textContent = `${new Intl.NumberFormat('fr-FR').format(result.litres)} L`;
            this.volumeTarget.textContent = `${format.format(result.cubicMetres)} m³`;
            this.fillingTarget.textContent = Number(data.waterPrice) > 0 ? money.format(budget.filling) : 'Tarif non renseigné';
            this.careTarget.textContent = Number(data.monthlyBudget) > 0 ? `${money.format(budget.care)} pour ${data.months} mois` : 'Budget non renseigné';
            this.resultTarget.hidden = false;
        } catch (error) { this.errorTarget.textContent = error.message; }
    }
}
