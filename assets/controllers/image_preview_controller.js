import { Controller } from '@hotwired/stimulus';

/*
 * Image field (admin product): preview of the chosen image before upload + file name and size.
 * The real check (type, 2 MB max) stays on the server (ProductType).
 */
const MAX_BYTES = 2 * 1024 * 1024;

export default class extends Controller {
    static targets = ['input', 'image', 'placeholder', 'name'];

    disconnect() {
        this.revoke();
    }

    change() {
        const file = this.inputTarget.files[0];
        this.revoke();

        if (!file) return;

        this.url = URL.createObjectURL(file);
        this.imageTarget.src = this.url;
        this.imageTarget.hidden = false;
        if (this.hasPlaceholderTarget) this.placeholderTarget.hidden = true;

        const size = file.size < 1024 * 1024 ? `${Math.round(file.size / 1024)} Ko` : `${(file.size / 1024 / 1024).toFixed(1).replace('.', ',')} Mo`;
        this.nameTarget.textContent = file.size > MAX_BYTES ? `${file.name} (${size}) : trop lourd, 2 Mo maximum` : `${file.name} (${size})`;
        this.nameTarget.classList.toggle('is-error', file.size > MAX_BYTES);
    }

    revoke() {
        if (this.url) URL.revokeObjectURL(this.url);
        this.url = null;
    }
}
