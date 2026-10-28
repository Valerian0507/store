import { Controller } from '@hotwired/stimulus';
import { analyseWater } from '../lib/pool_tools.js';

export default class extends Controller {
    static targets = ['form', 'result', 'error', 'ph', 'chlorine', 'alkalinity'];

    invalidate() { this.resultTarget.hidden = true; this.errorTarget.textContent = ''; }

    analyse(event) {
        event.preventDefault();
        this.resultTarget.hidden = true;
        this.errorTarget.textContent = '';
        if (!this.formTarget.reportValidity()) return;
        try {
            const data = Object.fromEntries(new FormData(this.formTarget));
            const result = analyseWater(data);
            const labels = { low: 'Sous le repère', high: 'Au-dessus du repère', within: 'Dans la plage de référence', review: 'À vérifier avec votre notice', reference: 'Plage de la notice non renseignée' };
            for (const name of ['ph', 'chlorine', 'alkalinity']) {
                const metric = result[name];
                this[`${name}Target`].textContent = `${new Intl.NumberFormat('fr-FR').format(metric.value)}${name === 'ph' ? '' : ' ppm'} — ${labels[metric.status]}`;
                this[`${name}Target`].dataset.status = metric.status;
            }
            this.resultTarget.hidden = false;
        } catch (error) { this.errorTarget.textContent = error.message; }
    }
}
