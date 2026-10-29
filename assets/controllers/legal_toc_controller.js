import { Controller } from '@hotwired/stimulus';

/*
 * Table of contents of the legal pages: highlights the section visible on screen.
 * Each link points to a section anchor (#id); without JS the links work as usual.
 */
export default class extends Controller {
    static targets = ['link'];

    connect() {
        this.sections = this.linkTargets
            .map((link) => document.getElementById(link.hash.slice(1)))
            .filter(Boolean);

        // Active section = the last one whose top has passed the upper third of the screen
        this.onScroll = () => {
            // After a click, keep the chosen link until scrolling ends (the section may be too low to reach the threshold)
            if (this.clickedId) {
                clearTimeout(this.unlockTimer);
                this.unlockTimer = setTimeout(() => { this.clickedId = null; }, 200);
                return;
            }

            const limit = window.innerHeight / 3;
            let current = this.sections[0];
            for (const section of this.sections) {
                if (section.getBoundingClientRect().top <= limit) current = section;
            }
            // At the bottom of the page, a short last section cannot go up to the upper third
            if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
                current = this.sections[this.sections.length - 1];
            }
            this.activate(current?.id);
        };

        window.addEventListener('scroll', this.onScroll, { passive: true });
        this.onScroll();
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScroll);
        clearTimeout(this.unlockTimer);
    }

    select(event) {
        this.clickedId = event.currentTarget.hash.slice(1);
        this.activate(this.clickedId);
        // Section already visible: no scrolling, so no scroll event to release the lock
        clearTimeout(this.unlockTimer);
        this.unlockTimer = setTimeout(() => { this.clickedId = null; }, 1000);
    }

    activate(id) {
        for (const link of this.linkTargets) {
            const active = link.hash === `#${id}`;
            link.classList.toggle('is-active', active);
            if (active) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        }
    }
}
