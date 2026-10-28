import { Controller } from '@hotwired/stimulus';

/*
 * Règles du mot de passe cochées en direct pendant la saisie.
 * Mêmes règles que côté serveur : src/Validator/StrongPassword.php (qui reste la vraie vérification).
 */
const RULES = {
    length: (value) => [...value].length >= 8,
    upper: (value) => /\p{Lu}/u.test(value),
    digit: (value) => /\d/.test(value),
    special: (value) => /[^\p{L}\p{N}\s]/u.test(value),
};

export default class extends Controller {
    static targets = ['input', 'rule'];

    connect() {
        this.check();
    }

    check() {
        const value = this.inputTarget.value;

        this.ruleTargets.forEach((rule) => {
            const ok = RULES[rule.dataset.rule]?.(value) ?? false;
            rule.classList.toggle('is-valid', ok);
        });
    }
}
