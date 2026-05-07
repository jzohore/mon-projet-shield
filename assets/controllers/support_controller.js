import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["overlay", "panel", "messages", "input"]

    // --- ANIMATIONS D'OUVERTURE / FERMETURE ---

    open() {
        // Affiche l'overlay et retire le scroll de la page derrière
        this.overlayTarget.classList.remove("hidden")
        document.body.classList.add("overflow-hidden")

        // Petit délai pour que la transition d'opacité fonctionne
        setTimeout(() => {
            this.overlayTarget.classList.replace("opacity-0", "opacity-100")
            this.panelTarget.classList.replace("translate-x-full", "translate-x-0")
        }, 10)

        // Scroll tout en bas pour voir les derniers messages
        this.scrollToBottom()

        // Met le focus directement dans le champ de texte !
        setTimeout(() => this.inputTarget.focus(), 500)
    }

    close() {
        // Cache le panneau et l'overlay
        this.panelTarget.classList.replace("translate-x-0", "translate-x-full")
        this.overlayTarget.classList.replace("opacity-100", "opacity-0")

        setTimeout(() => {
            this.overlayTarget.classList.add("hidden")
            document.body.classList.remove("overflow-hidden")
        }, 500)
    }

    // --- GESTION DU FORMULAIRE ---

    // --- FONCTIONS UTILITAIRES ---

    scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight
    }
}
