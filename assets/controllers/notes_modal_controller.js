import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["dialog", "content", "entityName"];

    open(event) {
        // 1. Récupération des données depuis le bouton cliqué
        const button = event.currentTarget;
        const entityName = button.dataset.entity;
        const notesRaw = button.dataset.notes;

        let notes = [];
        try {
            notes = JSON.parse(notesRaw);
        } catch (e) {
            console.error("Erreur de parsing des notes", e);
            return;
        }

        // 2. Mise à jour du nom de l'entité
        this.entityNameTarget.textContent = entityName;

        // 3. Construction du contenu HTML
        this.contentTarget.innerHTML = '';

        notes.forEach(note => {
            const wrapperDiv = document.createElement('div');
            wrapperDiv.className = "bg-slate-50 rounded-xl p-5 ring-1 ring-inset ring-slate-900/5";

            const paragraph = document.createElement('p');
            paragraph.className = "text-sm text-slate-700 leading-relaxed whitespace-pre-line font-medium break-words";
            paragraph.textContent = note; // textContent protège contre les failles XSS

            wrapperDiv.appendChild(paragraph);
            this.contentTarget.appendChild(wrapperDiv);
        });

        // 4. Ouverture de la modale native et blocage du scroll arrière
        this.dialogTarget.showModal();
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.dialogTarget.close();
        document.body.style.overflow = '';
    }

    // Permet de fermer la modale si on clique dans la zone grisée autour
    clickOutside(event) {
        if (event.target === this.dialogTarget) {
            this.close();
        }
    }
}
