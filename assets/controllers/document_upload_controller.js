import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String
    };

    static targets = ['input', 'button', 'icon', 'label'];

    // 1. Simule le clic sur l'input caché
    browse() {
        this.inputTarget.click();
    }

    // 2. S'exécute dès que l'utilisateur a choisi un fichier
    async upload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // On passe le bouton en mode "Chargement"
        this.buttonTarget.disabled = true;
        this.iconTarget.innerHTML = '<svg class="animate-spin size-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        this.labelTarget.textContent = 'Envoi...';

        // Préparation des données (FormData)
        const formData = new FormData();
        formData.append('document', file);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok && data.ok) {
                // Succès ! Le document est uploadé.
                // 🪄 ASTUCE UX : On force le LiveComponent parent à se recharger pour afficher le document !
                this.element.dispatchEvent(new CustomEvent('live:render', { bubbles: true }));

                this.buttonTarget.classList.replace('bg-slate-900', 'bg-emerald-600');
                this.iconTarget.innerHTML = '<svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                this.labelTarget.textContent = 'Ajouté';
            } else {
                throw new Error(data.message || "Erreur lors de l'upload");
            }

        } catch (error) {
            alert(error.message);

            // On remet le bouton à zéro
            this.iconTarget.innerHTML = '<svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path><polyline points="7 9 12 4 17 9"></polyline><line x1="12" y1="4" x2="12" y2="16"></line></svg>';
            this.labelTarget.textContent = 'Uploader';
            this.buttonTarget.disabled = false;
        } finally {
            this.inputTarget.value = '';
        }
    }
}
