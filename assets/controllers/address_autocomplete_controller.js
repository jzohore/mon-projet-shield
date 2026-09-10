import { Controller } from '@hotwired/stimulus';

/*
 * Recherche d'adresse via la Base Adresse Nationale (api-adresse.data.gouv.fr).
 * Service public, sans clé.
 *
 * Par défaut, seul le champ de recherche est visible. Le champ « adresse
 * postale » n'apparaît qu'après le choix d'une suggestion (résumé + bouton
 * « Modifier ») ou via « Saisir l'adresse manuellement ». La saisie à la main
 * reste toujours possible ; en cas d'erreur réseau on masque juste les
 * suggestions.
 */
export default class extends Controller {
    static targets = ['input', 'results', 'field', 'fieldWrapper', 'summary', 'summaryText'];
    static values = {
        min: { type: Number, default: 4 },
        url: { type: String, default: 'https://api-adresse.data.gouv.fr/search/' },
        hasError: { type: Boolean, default: false },
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

        // Adresse déjà renseignée (édition, ou re-rendu après soumission valide) :
        // on montre le résumé et on replie le champ. En cas d'erreur serveur sur
        // l'adresse, on laisse le champ ouvert pour la correction.
        const value = this.hasFieldTarget ? this.fieldTarget.value.trim() : '';
        if (value !== '' && !this.hasErrorValue) {
            this.#showSummary(value);
            this.#hideField();
        }
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

    /** « Modifier » : rouvre le champ pour ajuster l'adresse choisie. */
    edit() {
        this.#showField();
        this.#hideSummary();
        this.#focusField();
    }

    /** « Saisir l'adresse manuellement ». */
    manual() {
        this.#close();
        this.#showField();
        this.#hideSummary();
        this.#focusField();
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
            this.#renderResults(data.features || []);
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.#close();
            }
        }
    }

    #renderResults(features) {
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
        this.inputTarget.value = '';
        this.#close();
        this.#hideField();
        this.#showSummary(value);
    }

    #showSummary(value) {
        if (!this.hasSummaryTarget) {
            return;
        }
        this.summaryTextTarget.textContent = value;
        this.summaryTarget.hidden = false;
    }

    #hideSummary() {
        if (this.hasSummaryTarget) {
            this.summaryTarget.hidden = true;
        }
    }

    #showField() {
        if (this.hasFieldWrapperTarget) {
            this.fieldWrapperTarget.hidden = false;
        }
    }

    #hideField() {
        if (this.hasFieldWrapperTarget) {
            this.fieldWrapperTarget.hidden = true;
        }
    }

    #focusField() {
        if (this.hasFieldTarget) {
            this.fieldTarget.focus();
        }
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
