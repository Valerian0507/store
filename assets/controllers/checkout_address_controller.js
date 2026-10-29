import { Controller } from '@hotwired/stimulus';

/*
 * Checkout: "Utiliser une adresse enregistrée" (saved address) or "Nouvelle adresse" (new address).
 * Only the fields of the chosen mode are enabled (disabled fields are neither validated nor sent):
 * - saved address: sends selectedAddress = id of the checked address;
 * - new address: sends selectedAddress = 0 and the typed fields (CheckoutController::submit).
 */
export default class extends Controller {
    static targets = ['mode', 'saved', 'manual'];

    connect() {
        this.toggle();
    }

    toggle() {
        const manual = this.modeTargets.find((radio) => radio.checked)?.value !== 'saved';

        this.toggleSection(this.savedTarget, !manual);
        this.toggleSection(this.manualTarget, manual);
    }

    toggleSection(section, active) {
        if (!section) return;
        section.hidden = !active;
        section.querySelectorAll('input, select').forEach((field) => { field.disabled = !active; });
    }
}
