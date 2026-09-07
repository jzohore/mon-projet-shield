import { Controller } from '@hotwired/stimulus';

/*
 * Widget de configuration du compte (check-list d'onboarding).
 * - repli / dépli mémorisé dans le navigateur (localStorage) ;
 * - fermeture définitive persistée côté serveur (AJAX).
 */
export default class extends Controller {
    static targets = ['widget', 'content', 'header', 'chevron'];
    static values = { dismissUrl: String };

    connect() {
        this.collapsed = localStorage.getItem('onboarding_collapsed') === 'true';
        this.#apply(false);
    }

    toggle(event) {
        // On ne replie pas si le clic vient du bouton "fermer".
        if (event && event.target.closest('[data-action*="onboarding#dismiss"]')) return;

        this.collapsed = !this.collapsed;
        localStorage.setItem('onboarding_collapsed', this.collapsed);
        this.#apply(true);
    }

    async dismiss(event) {
        event.preventDefault();
        event.stopPropagation();

        localStorage.removeItem('onboarding_collapsed');
        this.widgetTarget.style.opacity = '0';
        this.widgetTarget.style.transform = 'translateY(20px)';
        setTimeout(() => this.widgetTarget.remove(), 300);

        if (!this.hasDismissUrlValue) return;
        try {
            await fetch(this.dismissUrlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
        } catch (e) {
            // Fermeture visuelle déjà faite : on ignore l'échec réseau.
        }
    }

    #apply(animate) {
        if (!animate && this.hasContentTarget) {
            this.contentTarget.style.transition = 'none';
        }

        if (this.hasContentTarget) {
            this.contentTarget.style.maxHeight = this.collapsed ? '0px' : '520px';
        }
        if (this.hasChevronTarget) {
            this.chevronTarget.style.transform = this.collapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
        }
        if (this.hasHeaderTarget) {
            this.headerTarget.setAttribute('aria-expanded', this.collapsed ? 'false' : 'true');
        }

        if (!animate && this.hasContentTarget) {
            setTimeout(() => {
                this.contentTarget.style.transition = '';
            }, 50);
        }
    }
}
