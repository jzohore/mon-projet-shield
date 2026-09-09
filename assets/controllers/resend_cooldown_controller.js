import { Controller } from '@hotwired/stimulus';

/*
 * Compte à rebours anti-spam sur un bouton d'action compact (icône seule).
 *
 *   data-resend-cooldown-seconds-value : secondes restantes au rendu (serveur).
 *
 *   <div data-controller="resend-cooldown" data-resend-cooldown-seconds-value="42">
 *     <button data-resend-cooldown-target="button" data-resend-cooldown-idle-class="...">
 *       <span data-resend-cooldown-target="count" hidden></span>
 *       <span data-resend-cooldown-target="icon"><!-- icône --></span>
 *     </button>
 *   </div>
 *
 * Tant qu'il reste du temps : bouton désactivé, icône masquée, secondes affichées.
 * À 0 : le bouton redevient cliquable.
 */
export default class extends Controller {
    static targets = ['button', 'count', 'icon'];
    static values = { seconds: Number };

    timer = null;
    remaining = 0;

    connect() {
        this.#start(this.secondsValue);
    }

    secondsValueChanged() {
        this.#start(this.secondsValue);
    }

    disconnect() {
        this.#clear();
    }

    #start(seconds) {
        this.#clear();
        this.remaining = Math.max(0, Math.floor(Number(seconds) || 0));
        this.#render();

        if (this.remaining <= 0) {
            return;
        }

        this.timer = setInterval(() => {
            this.remaining -= 1;
            this.#render();
            if (this.remaining <= 0) {
                this.#clear();
            }
        }, 1000);
    }

    #render() {
        const waiting = this.remaining > 0;

        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = waiting;
            this.buttonTarget.classList.toggle('opacity-40', waiting);
            this.buttonTarget.classList.toggle('cursor-not-allowed', waiting);
            this.buttonTarget.setAttribute('aria-disabled', waiting ? 'true' : 'false');
        }
        if (this.hasIconTarget) {
            this.iconTarget.hidden = waiting;
        }
        if (this.hasCountTarget) {
            this.countTarget.hidden = !waiting;
            this.countTarget.textContent = waiting ? String(this.remaining) : '';
        }
    }

    #clear() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}
