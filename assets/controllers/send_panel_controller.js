import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'form',
        'monitoringSection',
        'switchBg',
        'switchKnob',
        'monitoringInput'
    ];

    connect() {
        this.isMonitoring = false;
    }

    // Ouvre ou ferme le panneau principal
    toggleForm() {
        this.formTarget.classList.toggle('hidden');
    }

    // Gère l'animation du Switch iOS et affiche la durée
    toggleMonitoring() {
        this.isMonitoring = !this.isMonitoring;

        if (this.isMonitoring) {
            // Animer le switch en ON (Vert)
            this.switchBgTarget.classList.replace('bg-slate-200', 'bg-emerald-500');
            this.switchKnobTarget.classList.replace('translate-x-0', 'translate-x-5');
            // Afficher le choix de durée
            this.monitoringSectionTarget.classList.remove('hidden');
        } else {
            // Animer le switch en OFF (Gris)
            this.switchBgTarget.classList.replace('bg-emerald-500', 'bg-slate-200');
            this.switchKnobTarget.classList.replace('translate-x-5', 'translate-x-0');
            // Cacher le choix de durée
            this.monitoringSectionTarget.classList.add('hidden');
        }

        // CRUCIAL : Mettre à jour l'input caché pour Symfony UX Live Component
        this.monitoringInputTarget.value = this.isMonitoring ? 'true' : 'false';

        // On déclenche manuellement un événement "change" pour que le Live Component
        // comprenne que la valeur a changé et mette à jour le PHP en arrière-plan.
        this.monitoringInputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
