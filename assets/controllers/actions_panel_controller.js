import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Ce sont les deux "div" cachées de ton fichier Twig
    static targets = ['sendForm', 'monitoringForm'];

    // Quand on clique sur "Envoyer"
    toggleSend() {
        this.sendFormTarget.classList.toggle('hidden');
        this.monitoringFormTarget.classList.add('hidden'); // Ferme l'autre par sécurité
    }

    // Quand on clique sur "Surveiller"
    toggleMonitoring() {
        this.monitoringFormTarget.classList.toggle('hidden');
        this.sendFormTarget.classList.add('hidden'); // Ferme l'autre par sécurité
    }
}
