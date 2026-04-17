import { Controller } from '@hotwired/stimulus';

/*
 * Contrôleur de tracking Kysure
 * Utilisé pour enregistrer les clics stratégiques sans bloquer la navigation.
 */
// assets/controllers/click_tracking_controller.js
export default class extends Controller {
    static values = { name: String }

    log(event) {
        const urlParams = new URLSearchParams(window.location.search);

        // --- GÉNÉRATION DU SESSION ID ---
        let sessionId = sessionStorage.getItem('kysure_session_id');
        if (!sessionId) {
            // crypto.randomUUID() génère un UUID v4 parfait (ex: 123e4567-e89b-12d3-a456-426614174000)
            sessionId = crypto.randomUUID();
            sessionStorage.setItem('kysure_session_id', sessionId);
        }
        // --------------------------------

        const payload = {
            elementName: this.nameValue,
            pageUrl: window.location.href,
            referrer: document.referrer || null,
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            locale: navigator.language || navigator.userLanguage || null,
            sessionId: sessionId, // On envoie notre ID frontend
            utmData: {
                source: urlParams.get('utm_source'),
                medium: urlParams.get('utm_medium'),
                campaign: urlParams.get('utm_campaign')
            }
        };

        try {
            fetch('/track-click', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true
            });
        } catch (error) {
            console.error('Tracking error:', error);
        }
    }
}
