import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content', 'showText', 'hideText', 'iconDown', 'iconUp'];
    // On définit la classe à utiliser via Stimulus (par défaut 'hidden')
    static classes = ['hidden'];

    toggle(event) {
        event?.preventDefault();

        // Sécurité : S'il n'y a pas d'éléments cachés, on ne fait rien
        if (this.contentTargets.length === 0) return;

        // 💡 CORRECTION : On déduit l'état réel en regardant si le premier élément ciblé est masqué
        // Cela empêche toute désynchronisation avec LiveComponent
        const isCurrentlyHidden = this.contentTargets[0].classList.contains(this.hiddenClass);

        // On applique l'état inverse à tous les contenus
        this.contentTargets.forEach(target => {
            if (isCurrentlyHidden) {
                target.classList.remove(this.hiddenClass);
            } else {
                target.classList.add(this.hiddenClass);
            }
        });

        // Mise à jour de l'UI du bouton (Textes)
        if (this.hasShowTextTarget && this.hasHideTextTarget) {
            this.showTextTarget.classList.toggle(this.hiddenClass, isCurrentlyHidden);
            this.hideTextTarget.classList.toggle(this.hiddenClass, !isCurrentlyHidden);
        }

        // Mise à jour de l'UI du bouton (Icônes)
        if (this.hasIconDownTarget && this.hasIconUpTarget) {
            this.iconDownTarget.classList.toggle(this.hiddenClass, isCurrentlyHidden);
            this.iconUpTarget.classList.toggle(this.hiddenClass, !isCurrentlyHidden);
        }
    }
}
