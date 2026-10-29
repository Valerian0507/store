import { Controller } from '@hotwired/stimulus';

/*
 * Horizontal carousel: native scrolling (scroll-snap, swipe on mobile)
 * + previous / next buttons that move by one item.
 * The buttons are disabled at both ends and hidden when there is nothing to scroll.
 */
export default class extends Controller {
    static targets = ['track', 'prev', 'next'];

    connect() {
        this.update = this.update.bind(this);
        this.trackTarget.addEventListener('scroll', this.update, { passive: true });
        window.addEventListener('resize', this.update);
        this.update();
    }

    disconnect() {
        this.trackTarget.removeEventListener('scroll', this.update);
        window.removeEventListener('resize', this.update);
    }

    prev() {
        this.scrollByItem(-1);
    }

    next() {
        this.scrollByItem(1);
    }

    scrollByItem(direction) {
        const item = this.trackTarget.firstElementChild;
        if (!item) return;

        const gap = parseFloat(getComputedStyle(this.trackTarget).columnGap) || 0;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        this.trackTarget.scrollBy({
            left: direction * (item.offsetWidth + gap),
            behavior: reduceMotion ? 'auto' : 'smooth',
        });
    }

    update() {
        const { scrollLeft, scrollWidth, clientWidth } = this.trackTarget;
        const max = scrollWidth - clientWidth;

        this.element.classList.toggle('is-scrollable', max > 1);
        this.prevTarget.disabled = scrollLeft <= 1;
        this.nextTarget.disabled = scrollLeft >= max - 1;
    }
}
