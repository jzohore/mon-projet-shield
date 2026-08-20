import { Controller } from '@hotwired/stimulus';

/*
 * Contrôleur Stimulus pour les Popovers intelligents
 * Gère le positionnement dynamique pour éviter le débordement d'écran (Clipping)
 */
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

        // 1. On le rend invisible (mais présent dans le DOM) pour calculer ses dimensions
        popover.style.visibility = 'hidden';
        popover.classList.remove('hidden');

        // 2. On récupère les dimensions (le bouton et la largeur du popover)
        const rect = button.getBoundingClientRect();
        const popoverWidth = popover.offsetWidth;

        // 3. On le détache du flux HTML (Idéal ici car le header est sticky)
        popover.style.position = 'fixed';
        popover.style.top = `${rect.bottom + 6}px`; // 6px sous le bouton

        // 4. LE CALCUL INTELLIGENT (Prévention du débordement)
        if (rect.left + popoverWidth > window.innerWidth - 20) {
            // Débordement à droite : on aligne sur le bord droit du bouton
            popover.style.left = `${rect.right - popoverWidth}px`;
        } else {
            // Assez de place : on aligne sur le bord gauche du bouton
            popover.style.left = `${rect.left}px`;
        }

        // 5. On restaure la visibilité pour lancer l'animation Tailwind (animate-in)
        popover.style.visibility = 'visible';
    }

    close() {
        this.popoverTarget.classList.add('hidden');
    }

    clickOutside(event) {
        // 🛡️ GUARD 1 : Frugalité absolue (Évite le spam de @window)
        if (this.popoverTarget.classList.contains('hidden')) {
            return;
        }

        // 🛡️ GUARD 2 : Clic à l'intérieur du composant parent ou du popover lui-même
        if (this.element.contains(event.target) || this.popoverTarget.contains(event.target)) {
            return;
        }

        // ✅ Clic vérifié à l'extérieur : on ferme proprement
        this.close();
    }
}
