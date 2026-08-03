import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["input", "preview", "spinner", "errorMsg", "placeholderIcon"]
    static values = {
        url: String,
        s3BaseUrl: String
    }

    upload(event) {
        const file = this.inputTarget.files[0];
        if (!file) return;

        // 1. UI "En cours de traitement"
        this.errorMsgTarget.classList.add('hidden');
        this.errorMsgTarget.textContent = '';
        this.spinnerTarget.classList.remove('hidden');
        this.inputTarget.disabled = true; // Empêche les clics multiples
        this.inputTarget.classList.add('opacity-50', 'cursor-not-allowed');

        const formData = new FormData();
        formData.append('logo', file);

        fetch(this.urlValue, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.ok && data.redirectUrl) {
                    const baseUrl = this.s3BaseUrlValue.replace(/\/$/, '');
                    const path = data.newLogoPath.replace(/^\//, '');
                    // 🪄 L'astuce du Lead Dev : Le Cache Buster
                    // On génère un timestamp unique
                    const timestamp = new Date().getTime();

                    // On l'ajoute à la fin de l'URL (?v=123456789)
                    const fullImageUrl = `${baseUrl}/${path}?v=${timestamp}`;

                    // 2. On attend la propagation Scaleway (2 secondes suffisent largement)
                    setTimeout(() => {

                        // 3. 🪄 Astuce Pro : On précharge l'image virtuellement
                        const tempImg = new Image();

                        tempImg.onload = () => {
                            // L'image existe et est prête ! On met à jour l'UI.
                            this.previewTarget.src = fullImageUrl;
                            this.previewTarget.classList.remove('hidden');

                            if (this.hasPlaceholderIconTarget) {
                                this.placeholderIconTarget.classList.add('hidden');
                            }

                            this.stopLoading();
                        };

                        tempImg.onerror = () => {
                            // Sécurité : si l'image met plus de temps à se propager sur le CDN
                            this.previewTarget.src = fullImageUrl;
                            this.stopLoading();
                        };

                        // Déclenche le téléchargement
                        tempImg.src = fullImageUrl;

                    }, 9000);
                    window.location.href = data.redirectUrl;
                } else {
                    // Erreur métier (poids, format...)
                    this.stopLoading();
                    this.showError(data.message);
                }
            })
            .catch(error => {
                // Erreur serveur (500)
                this.stopLoading();
                this.showError("Une erreur réseau est survenue lors de l'envoi.");
            });
    }

    stopLoading() {
        this.spinnerTarget.classList.add('hidden');
        this.inputTarget.disabled = false;
        this.inputTarget.classList.remove('opacity-50', 'cursor-not-allowed');
    }

    showError(message) {
        this.errorMsgTarget.textContent = message;
        this.errorMsgTarget.classList.remove('hidden');
        this.inputTarget.value = ''; // On vide le champ pour forcer un nouveau clic
    }
}
