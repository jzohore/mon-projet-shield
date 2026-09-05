import { Controller } from '@hotwired/stimulus';

/*
 * Menu déroulant accessible (avatar, actions…).
 * - `data-dropdown-target="button"` : le déclencheur (porte aria-expanded)
 * - `data-dropdown-target="menu"`   : le panneau (masqué via la classe `hidden`)
 * Ferme au clic extérieur et sur Échap ; rend le focus au bouton.
 */
export default class extends Controller {
    static targets = ['button', 'menu'];

    connect() {
        this._onOutside = this._onOutside.bind(this);
        this._onKeydown = this._onKeydown.bind(this);
        this.close();
    }

    disconnect() {
        this._removeListeners();
    }

    toggle() {
        this.menuTarget.classList.contains('hidden') ? this.open() : this.close();
    }

    open() {
        this.menuTarget.classList.remove('hidden');
        this.buttonTarget.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', this._onOutside, true);
        document.addEventListener('keydown', this._onKeydown, true);
    }

    close() {
        this.menuTarget.classList.add('hidden');
        this.buttonTarget.setAttribute('aria-expanded', 'false');
        this._removeListeners();
    }

    _removeListeners() {
        document.removeEventListener('click', this._onOutside, true);
        document.removeEventListener('keydown', this._onKeydown, true);
    }

    _onOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    _onKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
            this.buttonTarget.focus();
        }
    }
}
