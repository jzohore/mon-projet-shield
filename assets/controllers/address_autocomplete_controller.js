import { Controller } from '@hotwired/stimulus';

/*
 * Recherche d'adresse via la Base Adresse Nationale (api-adresse.data.gouv.fr).
 * Service public, sans clé. La saisie manuelle reste toujours possible : en cas
 * d'erreur réseau, le contrôleur se contente de masquer les suggestions.
 *
 *   <div data-controller="address-autocomplete" data-address-autocomplete-min-value="4">
 *     <input data-address-autocomplete-target="input"
 *            data-action="input->address-autocomplete#search keydown->address-autocomplete#keydown">
 *     <div data-address-autocomplete-target="results" hidden></div>
 *     <textarea data-address-autocomplete-target="field"></textarea>
 *   </div>
 */
export default class extends Controller {
    static targets = ['input', 'results', 'field'];
    static values = {
        min: { type: Number, default: 4 },
        url: { type: String, default: 'https://api-adresse.data.gouv.fr/search/' },
    };

    #timer = null;
    #controller = null;

    connect() {
        this.onClickOutside = (event) => {
            if (!this.element.contains(event.target)) {
                this.#close();
            }
        };
        document.addEventListener('click', this.onClickOutside);
    }

    disconnect() {
        document.removeEventListener('click', this.onClickOutside);
        this.#abort();
        clearTimeout(this.#timer);
    }

    search() {
        clearTimeout(this.#timer);
        const query = this.inputTarget.value.trim();

        if (query.length < this.minValue) {
            this.#close();
            return;
        }

        this.#timer = setTimeout(() => this.#fetch(query), 250);
    }

    keydown(event) {
        if (event.key === 'Escape') {
            this.#close();
        }
    }

    async #fetch(query) {
        this.#abort();
        this.#controller = new AbortController();

        try {
            const url = `${this.urlValue}?q=${encodeURIComponent(query)}&limit=6`;
            const response = await fetch(url, { signal: this.#controller.signal });
            if (!response.ok) {
                this.#close();
                return;
            }
            const data = await response.json();
            this.#render(data.features || []);
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.#close();
            }
        }
    }

    #render(features) {
        if (features.length === 0) {
            this.#close();
            return;
        }

        this.resultsTarget.innerHTML = '';
        features.forEach((feature) => {
            const p = feature.properties || {};
            const button = document.createElement('button');
            button.type = 'button';
            button.className =
                'block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-900 focus:bg-indigo-50 focus:outline-none';
            button.textContent = p.label || '';
            button.addEventListener('click', () => this.#choose(p));
            this.resultsTarget.appendChild(button);
        });
        this.resultsTarget.hidden = false;
    }

    #choose(p) {
        const line1 = [p.name, p.locality].filter(Boolean).join(', ');
        const line2 = [p.postcode, p.city].filter(Boolean).join(' ');
        const value = [line1, line2].filter(Boolean).join('\n');

        if (this.hasFieldTarget) {
            this.fieldTarget.value = value;
            this.fieldTarget.dispatchEvent(new Event('input', { bubbles: true }));
            this.fieldTarget.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.inputTarget.value = p.label || value;
        this.#close();
    }

    #close() {
        this.resultsTarget.hidden = true;
        this.resultsTarget.innerHTML = '';
    }

    #abort() {
        if (this.#controller) {
            this.#controller.abort();
            this.#controller = null;
        }
    }
}
