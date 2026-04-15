import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["content", "label", "icon"];

    toggle() {
        // 1. On affiche ou masque le contenu
        this.contentTarget.classList.toggle("hidden");

        // 2. On change l'état du bouton
        const isHidden = this.contentTarget.classList.contains("hidden");

        if (isHidden) {
            this.labelTarget.textContent = this.labelTarget.dataset.textMore;
            this.iconTarget.classList.remove("rotate-180");
        } else {
            this.labelTarget.textContent = this.labelTarget.dataset.textLess;
            this.iconTarget.classList.add("rotate-180");
        }
    }
}
