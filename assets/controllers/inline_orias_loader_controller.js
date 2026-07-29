import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['icon', 'spinner', 'title', 'subtitle', 'details'];

    showLoading() {
        // 1. Switch de l'icône vers le spinner
        if (this.hasIconTarget) this.iconTarget.classList.add('hidden');
        if (this.hasSpinnerTarget) this.spinnerTarget.classList.remove('hidden');

        // 2. Mise à jour des textes
        if (this.hasTitleTarget) {
            this.titleTarget.textContent = 'Vérification en cours...';
            this.titleTarget.classList.add('text-blue-600', 'animate-pulse');
        }
        if (this.hasSubtitleTarget) {
            this.subtitleTarget.textContent = 'Interrogation du registre officiel';
            this.subtitleTarget.className = 'text-[10px] text-blue-500 font-medium animate-pulse';
        }

        // 3. Mise en retrait (fade) des anciens statuts (badges, numéros)
        if (this.hasDetailsTarget) {
            this.detailsTarget.classList.add('opacity-30', 'grayscale');
        }
    }
}
