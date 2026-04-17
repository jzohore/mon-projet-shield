import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'backdrop'];

    open() {
        // Affiche la sidebar
        this.menuTarget.classList.remove('-translate-x-full');

        // Affiche le fond sombre
        this.backdropTarget.classList.remove('hidden');
        // Petit timeout pour l'effet de fade-in
        setTimeout(() => {
            this.backdropTarget.classList.remove('opacity-0');
        }, 10);
    }

    close() {
        // Cache la sidebar
        this.menuTarget.classList.add('-translate-x-full');

        // Cache le fond sombre avec fade-out
        this.backdropTarget.classList.add('opacity-0');
        setTimeout(() => {
            this.backdropTarget.classList.add('hidden');
        }, 300); // Correspond à la durée de la transition Tailwind (duration-300)
    }
}
