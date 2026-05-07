// assets/controllers/scroll_bottom_controller.js
import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.scrollToBottom();

        // On observe également les mutations du DOM à l'intérieur du conteneur
        // Utile si des éléments sont ajoutés sans que le contrôleur lui-même soit reconnecté
        this.observer = new MutationObserver(() => this.scrollToBottom());
        this.observer.observe(this.element, {
            childList: true,
            subtree: true
        });
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    /**
     * Aligne le scroll en bas du conteneur
     */
    scrollToBottom() {
        this.element.scrollTo({
            top: this.element.scrollHeight,
            behavior: 'smooth' // 'smooth' pour un glissement fluide, 'instant' pour un saut immédiat
        });
    }
}
