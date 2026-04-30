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

    submitOnEnter(event) {
        // Si on tape "Entrée" sans appuyer sur "Maj" (Shift)
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault()
            // Déclenche l'envoi du formulaire
            event.target.closest('form').requestSubmit()
        }
    }

    async sendMessage(event) {
        event.preventDefault()

        const form = event.target
        const formData = new FormData(form)
        const messageText = formData.get('message').trim()

        if (!messageText) return

        // 1. OPTIMISTIC UI : On affiche la bulle IMMÉDIATEMENT (L'effet Woaow)
        this.appendClientBubble(messageText)

        // On vide le champ et on scroll en bas
        this.inputTarget.value = ''
        this.inputTarget.style.height = 'auto' // Reset la hauteur si auto-resize
        this.scrollToBottom()

        // 2. ENVOI EN ARRIÈRE-PLAN (AJAX vers Symfony)
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Indique à Symfony que c'est de l'AJAX
                }
            })

            if (!response.ok) throw new Error('Erreur serveur')

            // C'est bon, le message est sauvegardé en BDD et envoyé sur ton Slack !
        } catch (error) {
            console.error('Impossible d\'envoyer le message', error)
            // Tu pourrais afficher un petit texte "Erreur d'envoi" sous la bulle
        }
    }

    // --- FONCTIONS UTILITAIRES ---

    appendClientBubble(text) {
        // Sécurise le texte pour éviter les failles XSS (très important !)
        const safeText = text.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\n/g, "<br>")

        const bubbleHTML = `
            <div class="flex gap-3 max-w-[85%] ml-auto justify-end">
                <div>
                    <div class="bg-indigo-600 text-white text-sm p-3 rounded-2xl rounded-tr-none shadow-sm">
                        ${safeText}
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 mr-1 block text-right">À l'instant</span>
                </div>
            </div>
        `
        this.messagesTarget.insertAdjacentHTML('beforeend', bubbleHTML)
    }

    scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight
    }
}
