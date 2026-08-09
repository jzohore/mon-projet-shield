import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['popover'];

    toggle(event) {
        event.stopPropagation();

        if (this.popoverTarget.classList.contains('hidden')) {
            this.open(event.currentTarget);
        } else {
            this.close();
        }
    }

    open(button) {
        const popover = this.popoverTarget;

        // 1. On le rend invisible (mais présent dans le DOM) pour éviter l'effet de "slide"
        popover.style.visibility = 'hidden';
        popover.classList.remove('hidden');

        // 2. On récupère les dimensions
        const rect = button.getBoundingClientRect();
        const popoverWidth = popover.offsetWidth;

        // 3. On le détache du flux HTML
        popover.style.position = 'fixed';
        popover.style.top = `${rect.bottom + 6}px`; // 6px sous le bouton

        // 4. LE CALCUL INTELLIGENT
        // Est-ce que si je l'aligne à gauche, il déborde de l'écran à droite ?
        if (rect.left + popoverWidth > window.innerWidth - 20) {
            // Oui il déborde ! Donc on l'aligne sur le bord droit du bouton
            popover.style.left = `${rect.right - popoverWidth}px`;
        } else {
            // Non il a la place ! On l'aligne tranquillement sur le bord gauche du bouton
            popover.style.left = `${rect.left}px`;
        }

        // 5. On restaure la visibilité (ce qui déclenche proprement l'animation Tailwind animate-in)
        popover.style.visibility = 'visible';
    }

    close() {
        this.popoverTarget.classList.add('hidden');
    }

    clickOutside(event) {
        if (!this.element.contains(event.target) && !this.popoverTarget.contains(event.target)) {
            this.close();
        }
    }
}
