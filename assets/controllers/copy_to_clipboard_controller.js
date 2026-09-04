import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
// Copie une empreinte (ou tout texte court) dans le presse-papiers, avec un
// retour visuel bref sur le bouton déclencheur. Utilisé pour les empreintes
// SHA-256 de preuve (accusé de réception du DER, attestation).
export default class extends Controller {
    static targets = ["source", "icon", "label"];
    static values = { resetDelay: { type: Number, default: 1500 } };

    async copy() {
        const text = this.sourceTarget.textContent.trim();

        try {
            await navigator.clipboard.writeText(text);
        } catch {
            return;
        }

        if (this.hasLabelTarget) {
            const original = this.labelTarget.textContent;
            this.labelTarget.textContent = "Copié !";
            window.setTimeout(() => {
                this.labelTarget.textContent = original;
            }, this.resetDelayValue);
        }

        if (this.hasIconTarget) {
            this.iconTarget.classList.add("text-emerald-600");
            window.setTimeout(() => {
                this.iconTarget.classList.remove("text-emerald-600");
            }, this.resetDelayValue);
        }
    }
}
