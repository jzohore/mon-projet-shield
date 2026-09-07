import { Controller } from '@hotwired/stimulus';

/*
 * Compte à rebours pour le bouton « Renvoyer le lien ».
 *
 * Le serveur (LiveComponent) fournit le nombre de secondes restantes via
 * data-resend-countdown-seconds-value. Tant qu'il est > 0, le bouton est
 * désactivé et affiche « Renvoyer dans Ns ». À 0, il redevient cliquable.
 *
 *   <div data-controller="resend-countdown" data-resend-countdown-seconds-value="60">
 *     <button data-resend-countdown-target="button"
 *             data-action="live#action" data-live-action-param="resend">
 *       <span data-resend-countdown-target="label">Renvoyer le lien</span>
 *     </button>
 *   </div>
 */
export default class extends Controller {
    static targets = ['button', 'label'];
    static values = { seconds: Number };

    connect() {
        this.defaultLabel = this.hasLabelTarget ? this.labelTarget.textContent.trim() : 'Renvoyer le lien';
        this.#start(this.secondsValue);
    }

    disconnect() {
        this.#clear();
    }

    secondsValueChanged() {
        // Le serveur a renvoyé une nouvelle valeur (après un envoi) : on relance.
        this.#start(this.secondsValue);
    }

    onClick() {
        // Retour visuel immédiat en attendant la réponse serveur.
        this.#start(this.secondsValue > 0 ? this.secondsValue : 60);
    }

    #start(seconds) {
        this.#clear();
        this.remaining = Math.max(0, Math.floor(seconds || 0));
        this.#render();

        if (this.remaining <= 0) return;

        this.timer = setInterval(() => {
            this.remaining -= 1;
            this.#render();
            if (this.remaining <= 0) this.#clear();
        }, 1000);
    }

    #render() {
        if (!this.hasButtonTarget) return;

        const waiting = this.remaining > 0;
        this.buttonTarget.disabled = waiting;
        this.buttonTarget.classList.toggle('opacity-50', waiting);
        this.buttonTarget.classList.toggle('cursor-not-allowed', waiting);

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = waiting
                ? `Renvoyer dans ${this.remaining} s`
                : this.defaultLabel;
        }
    }

    #clear() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}
