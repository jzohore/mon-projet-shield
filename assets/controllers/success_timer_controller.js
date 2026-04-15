import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    connect() {
        setTimeout(async () => {

            // 1. On ferme le panneau gris
            const panel = this.element.closest('[data-actions-panel-target="sendForm"]');
            if (panel) {
                panel.classList.add('hidden');
            }

            // 2. On contacte le serveur PHP
            const liveElement = this.element.closest('[data-controller~="live"]');

            if (!liveElement) return;

            try {
                const component = await getComponent(liveElement);

                // 3. On remet la variable à false (maintenant PHP l'accepte !)
                component.set('isDocumentSent', false);

                // 🔥 NOUVEAU : On force Symfony à re-rendre le Twig immédiatement en arrière-plan
                await component.render();

            } catch (error) {
                console.error("❌ Erreur UX Live Component :", error);
            }

        }, 2000);
    }
}
