import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icon', 'spinner', 'title', 'subtitle', 'details'];

    // Définition des propriétés dynamiques (avec des valeurs par défaut sécurisées)
    static values = {
        loadingTitle: { type: String, default: 'Vérification en cours...' },
        loadingSubtitle: { type: String, default: 'Interrogation du registre...' },
        colorClass: { type: String, default: 'text-blue-600' }
    }

    showLoading() {
        // 1. Switch de l'icône vers le spinner
        if (this.hasIconTarget) this.iconTarget.classList.add('hidden');
        if (this.hasSpinnerTarget) this.spinnerTarget.classList.remove('hidden');

        // 2. Mise à jour dynamique des textes et de la couleur
        if (this.hasTitleTarget) {
            this.titleTarget.textContent = this.loadingTitleValue;
            this.titleTarget.classList.add(this.colorClassValue, 'animate-pulse');
        }
        if (this.hasSubtitleTarget) {
            this.subtitleTarget.textContent = this.loadingSubtitleValue;
            // On conserve la structure compacte text-[9px] mais on applique la couleur dynamique
            this.subtitleTarget.className = `text-[9px] font-medium animate-pulse ${this.colorClassValue}`;
        }

        // 3. Mise en retrait (fade) de la zone des statuts
        if (this.hasDetailsTarget) {
            this.detailsTarget.classList.add('opacity-30', 'grayscale');
        }
    }
}
