// assets/controllers/form_dirty_controller.js
import { Controller } from '@hotwired/stimulus';

// 🪄 L'astuce anti-amnésie :
// Cette variable survit aux rechargements partiels du DOM par Live Component
let savedInitialData = null;

export default class extends Controller {
    static targets = ["submitButton"]

    connect() {
        // 1. On capture l'état initial UNE SEULE FOIS au chargement de la page
        if (!savedInitialData) {
            savedInitialData = this.getFormDataString();
        }

        // 2. L'astuce anti-aveuglement : Le MutationObserver
        // Il détecte quand LiveComponent ajoute ou supprime des partenaires (balises HTML)
        this.observer = new MutationObserver(() => {
            this.check();
        });

        // On surveille tout ce qui s'ajoute ou s'enlève à l'intérieur du formulaire
        this.observer.observe(this.element, {
            childList: true, // Écoute les suppressions/ajouts de balises
            subtree: true    // Écoute aussi à l'intérieur des sous-divs
        });

        // 3. Vérification initiale
        this.check();
    }

    disconnect() {
        // Nettoyage indispensable pour éviter de faire ralentir le navigateur
        if (this.observer) {
            this.observer.disconnect();
        }

        // Si on quitte vraiment la page, on réinitialise la mémoire
        // (Utile si tu utilises Turbo Drive)
        if (!document.body.contains(this.element)) {
            savedInitialData = null;
        }
    }

    check() {
        const currentData = this.getFormDataString();

        // On compare avec notre variable globale, pas avec une variable d'instance
        const isDirty = savedInitialData !== currentData;

        // Sécurité : au cas où le bouton n'est pas encore rendu dans le DOM
        if (!this.hasSubmitButtonTarget) return;

        this.submitButtonTarget.disabled = !isDirty;

        if (isDirty) {
            this.submitButtonTarget.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            this.submitButtonTarget.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    getFormDataString() {
        const form = this.element.querySelector('form');
        // Si Live Component est en train de recharger, le form peut être introuvable 1 ms
        if (!form) return '';

        return new URLSearchParams(new FormData(form)).toString();
    }
}
