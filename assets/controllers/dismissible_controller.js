import { Controller } from '@hotwired/stimulus';

/*
 * Masque un bloc quand l'utilisateur le ferme, et retient ce choix pour la
 * durée de la session (sessionStorage). Le bloc réapparaît à la session
 * suivante tant que la condition serveur qui l'affiche reste vraie.
 *
 * Usage :
 *   <div data-controller="dismissible" data-dismissible-key-value="twofa-nudge" hidden>
 *     ...
 *     <button data-action="dismissible#dismiss">Fermer</button>
 *   </div>
 */
export default class extends Controller {
    static values = { key: String };

    connect() {
        if (this.#isDismissed()) {
            this.element.remove();
            return;
        }
        this.element.hidden = false;
    }

    dismiss() {
        try {
            sessionStorage.setItem(this.#storageKey(), '1');
        } catch (e) {
            // sessionStorage indisponible (navigation privée stricte) : on ferme quand même.
        }
        this.element.remove();
    }

    #storageKey() {
        return `dismissible:${this.keyValue || 'default'}`;
    }

    #isDismissed() {
        try {
            return sessionStorage.getItem(this.#storageKey()) === '1';
        } catch (e) {
            return false;
        }
    }
}
