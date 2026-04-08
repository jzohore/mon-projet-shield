import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'fileName', 'actionsContainer', 'uploadLabel', 'submitLabel', 'spinner'];

    preview() {
        const file = this.inputTarget.files[0];

        if (!file) {
            this.reset();
            return;
        }

        this.fileNameTarget.textContent = file.name;
        this.actionsContainerTarget.classList.remove('hidden');
        this.actionsContainerTarget.classList.add('flex');
        this.uploadLabelTarget.classList.add('hidden');
        this.clearError(); // <-- NOUVEAU : On nettoie si l'utilisateur change de fichier
    }

    reset() {
        this.inputTarget.value = '';
        this.fileNameTarget.textContent = '';
        this.actionsContainerTarget.classList.remove('flex');
        this.actionsContainerTarget.classList.add('hidden');
        this.uploadLabelTarget.classList.remove('hidden');
        this.clearError(); // <-- NOUVEAU
    }

    async upload(event) {
        event.preventDefault();

        const file = this.inputTarget.files[0];
        if (!file) {
            this.reset();
            return;
        }

        const uploadUrl = this.element.dataset.uploadUrl;
        const folderSlugId = this.element.dataset.folderSlugId;
        const slotId = this.element.dataset.slotId;

        if (!uploadUrl || !folderSlugId || !slotId) {
            console.error('KycDocumentUploader: données manquantes');
            return;
        }

        this.clearError(); // <-- NOUVEAU

        const formData = new FormData();
        formData.append('document', file);
        formData.append('folderSlugId', folderSlugId);

        this.submitLabelTarget.classList.add('hidden');
        this.spinnerTarget.classList.remove('hidden');

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json' // <-- NOUVEAU : On exige du JSON
                },
            });

            // NOUVEAU : On protège le parsing car une erreur 413/422 PHP peut renvoyer du HTML
            let payload;
            try {
                payload = await response.json();
            } catch (jsonError) {
                throw new Error('Fichier trop volumineux ou refusé par le serveur.');
            }

            if (!response.ok || !payload.ok) {
                // On tente de récupérer le message d'erreur précis de Symfony
                throw new Error(payload.error || payload.message || 'L’upload a échoué.');
            }

            this.element.dispatchEvent(new CustomEvent('kyc:uploaded', {
                bubbles: true,
                detail: {
                    id: slotId,
                    fileName: payload.fileName,
                },
            }));
        } catch (e) {
            // NOUVEAU : Affichage visuel immédiat
            this.showError(e.message);

            this.element.dispatchEvent(new CustomEvent('kyc:upload-error', {
                bubbles: true,
                detail: {
                    id: slotId,
                    message: e.message,
                },
            }));
        } finally {
            this.submitLabelTarget.classList.remove('hidden');
            this.spinnerTarget.classList.add('hidden');
        }
    }

    // =========================================================
    // NOUVELLES MÉTHODES (Injection du visuel d'erreur)
    // =========================================================

    showError(message) {
        this.clearError();
        const errorHtml = `
            <div id="upload-error-${this.element.dataset.slotId}" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 flex items-start gap-2 animate-in fade-in">
                <svg class="size-4 mt-0.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 8l0 4" /><path d="M12 16l.01 0" /></svg>
                <span>${message}</span>
            </div>
        `;
        // On place l'erreur juste en dessous des boutons d'action
        this.actionsContainerTarget.insertAdjacentHTML('afterend', errorHtml);
    }

    clearError() {
        const existingError = document.getElementById(`upload-error-${this.element.dataset.slotId}`);
        if (existingError) {
            existingError.remove();
        }
    }
}
