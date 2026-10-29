import { Controller } from '@hotwired/stimulus';

/*
 * Password rules ticked live while typing.
 * Same rules as the server: src/Validator/StrongPassword.php (which remains the real check).
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
