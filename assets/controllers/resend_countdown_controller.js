import { Controller } from '@hotwired/stimulus';

/*
 * Compte à rebours pour le bouton « Renvoyer le lien ».
 *
 * - data-resend-countdown-remaining-value : secondes restantes fournies par le
 *   serveur (LiveComponent) au rendu.
 * - data-resend-countdown-cooldown-value  : durée totale du cooldown (défaut 60).
 * - data-resend-countdown-label-value     : libellé du bouton au repos.
 *
 * Tant qu'il reste du temps, le bouton est désactivé et affiche
 * « Renvoyer dans N s ». À 0, il redevient un bouton d'action cliquable.
 *
 *   <div data-controller="resend-countdown"
 *        data-resend-countdown-remaining-value="60"
 *        data-resend-countdown-cooldown-value="60"
 *        data-resend-countdown-label-value="Renvoyer le lien">
 *     <button data-resend-countdown-target="button"
 *             data-action="live#action resend-countdown#restart"
 *             data-live-action-param="resend">
 *       <span data-resend-countdown-target="label">Renvoyer le lien</span>
 *     </button>
 *   </div>
 */
export default class extends Controller {
    static targets = ['button', 'label'];
    static values = {
        remaining: Number,
        cooldown: { type: Number, default: 60 },
        label: { type: String, default: 'Renvoyer le lien' },
    };

    #ready = false;
    timer = null;
    count = 0;

    connect() {
        this.#ready = true;
        this.#run(this.remainingValue);
    }

    disconnect() {
        this.#ready = false;
        this.#clear();
    }

    // Le serveur a renvoyé une nouvelle valeur après un envoi.
    remainingValueChanged() {
        if (this.#ready) {
            this.#run(this.remainingValue);
        }
    }

    // Clic sur le bouton : on relance immédiatement le décompte (retour visuel),
    // l'action LiveComponent `resend` part en parallèle.
    restart() {
        this.#run(this.cooldownValue);
    }

    #run(seconds) {
        this.#clear();
        this.count = Math.max(0, Math.floor(Number(seconds) || 0));
        this.#render();

        if (this.count <= 0) {
            return;
        }

        this.timer = setInterval(() => {
            this.count -= 1;
            this.#render();
            if (this.count <= 0) {
                this.#clear();
            }
        }, 1000);
    }

    #render() {
        const waiting = this.count > 0;

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = waiting;
            this.buttonTarget.classList.toggle('opacity-50', waiting);
            this.buttonTarget.classList.toggle('cursor-not-allowed', waiting);
            this.buttonTarget.setAttribute('aria-disabled', waiting ? 'true' : 'false');
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = waiting
                ? 'Renvoyer dans ' + this.count + ' s'
                : this.labelValue;
        }
    }

    #clear() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}
