import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    // Permet de configurer le temps avant disparition (par défaut 5 secondes)
    static values = {
        timeout: { type: Number, default: 5000 }
    }

    connect() {
        // Déclenche l'auto-fermeture si le timeout est supérieur à 0
        if (this.timeoutValue > 0) {
            this.timeout = setTimeout(() => {
                this.dismiss();
            }, this.timeoutValue);
        }
    }

    disconnect() {
        // Nettoyage de la mémoire si le composant est détruit prématurément
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }

    dismiss() {
        // 1. On ajoute les classes Tailwind pour animer la sortie (fondu + glissement à droite)
        this.element.classList.remove('translate-x-0', 'opacity-100');
        this.element.classList.add('translate-x-full', 'opacity-0');

        // 2. On attend la fin de l'animation CSS (ex: 300ms) avant de retirer l'élément du DOM
        setTimeout(() => {
            this.element.remove();
        }, 300);
    }
}
