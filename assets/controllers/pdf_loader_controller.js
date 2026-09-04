import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
// Masque l'overlay de chargement dès que l'iframe du PDF a fini de charger
// (utile sur connexion lente : la page publique d'accusé de réception du DER
// ne doit jamais sembler cassée pendant le chargement).
export default class extends Controller {
    static targets = ["overlay"];

    hideOverlay() {
        this.overlayTarget.classList.add("hidden");
    }
}
