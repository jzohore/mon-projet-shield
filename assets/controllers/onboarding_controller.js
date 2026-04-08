import { Controller } from '@hotwired/stimulus';

/*
 * Gère le widget de démarrage (rétractation + masquage définitif via AJAX)
 */
export default class extends Controller {
    // J'ai gardé progressText au cas où tu l'ajoutes plus tard, mais rendu son appel sécurisé
    static targets = ['widget', 'content', 'progressText'];

    static values = {
        dismissUrl: String
    };

    connect() {
        // 1. Au chargement, on lit l'état sauvegardé dans le navigateur
        this.isCollapsed = localStorage.getItem('onboarding_collapsed') === 'true';

        // 2. On applique l'état (le paramètre 'false' désactive l'animation au chargement initial)
        this.applyState(false);
    }

    toggle(event) {
        if (event && event.target.closest('[data-action="click->onboarding#dismiss"]')) return;
        if (event && event.target.tagName === 'A') return;

        // 3. On inverse l'état
        this.isCollapsed = !this.isCollapsed;

        // 4. On sauvegarde le nouvel état dans le navigateur
        localStorage.setItem('onboarding_collapsed', this.isCollapsed);

        // 5. On applique visuellement avec animation
        this.applyState(true);
    }

    applyState(animate = true) {
        // Si on ne veut pas d'animation (au chargement), on coupe temporairement les transitions CSS
        if (!animate) {
            this.contentTarget.style.transition = 'none';
            this.widgetTarget.style.transition = 'none';
        }

        if (this.isCollapsed) {
            // Rétracter
            this.contentTarget.style.maxHeight = '0px';
            if (this.hasProgressTextTarget) this.progressTextTargetTarget.classList.remove('hidden');
            this.widgetTarget.classList.add('w-64');
            this.widgetTarget.classList.remove('w-85'); // J'ai corrigé w-80 en w-85 pour matcher ton HTML
        } else {
            // Agrandir
            this.contentTarget.style.maxHeight = '450px';
            if (this.hasProgressTextTarget) this.progressTextTargetTarget.classList.add('hidden');
            this.widgetTarget.classList.remove('w-64');
            this.widgetTarget.classList.add('w-85');
        }

        // On remet les transitions après un très court délai
        if (!animate) {
            setTimeout(() => {
                this.contentTarget.style.transition = '';
                this.widgetTarget.style.transition = '';
            }, 50);
        }
    }

    async dismiss(event) {
        event.preventDefault();
        event.stopPropagation();

        // Optionnel : on nettoie le localStorage vu que le widget est définitivement fermé
        localStorage.removeItem('onboarding_collapsed');

        // Animation visuelle immédiate
        this.widgetTarget.style.opacity = '0';
        this.widgetTarget.style.transform = 'translateY(20px)';

        setTimeout(() => {
            this.widgetTarget.remove();
        }, 300);

        // Requête AJAX
        if (this.hasDismissUrlValue) {
            try {
                const response = await fetch(this.dismissUrlValue, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    console.error('Erreur lors de la sauvegarde du widget onboarding.');
                }
            } catch (error) {
                console.error('Erreur réseau lors de la fermeture du widget:', error);
            }
        }
    }
}
