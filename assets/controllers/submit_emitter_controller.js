import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        eventName: String // Le nom de l'événement à dispatcher (ex: kysure:orias-loading)
    }
    static targets = ['button'];

    submit() {
        // 1. Déclenchement d'un événement global (Event-Driven Front)
        window.dispatchEvent(new CustomEvent(this.eventNameValue));

        // 2. Désactivation du bouton différée (pour laisser passer la requête POST natif)
        setTimeout(() => {
            if (this.hasButtonTarget) {
                this.buttonTarget.disabled = true;
            }
        }, 0);
    }
}
