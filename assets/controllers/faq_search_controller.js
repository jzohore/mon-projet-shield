import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["item", "empty"]

    filter(event) {
        // 1. On récupère la saisie, on met en minuscules
        const rawSearch = event.target.value.toLowerCase().trim()

        // 2. TOKENISATION : On sépare la recherche en un tableau de mots (ex: ["mon", "client"])
        // L'expression régulière /\s+/ permet de gérer les espaces multiples proprement
        const searchTerms = rawSearch === "" ? [] : rawSearch.split(/\s+/)

        let visibleCount = 0

        this.itemTargets.forEach(item => {
            // 3. FUSION DES CONTEXTES
            // On récupère tout le texte visible à l'écran (Titre + Paragraphe)
            const visibleText = item.textContent.toLowerCase()

            // On récupère les mots clés cachés (gestion de la sécurité si l'attribut manque)
            const hiddenKeywords = item.dataset.searchableText ? item.dataset.searchableText.toLowerCase() : ""

            // Le texte global dans lequel on va chercher
            const fullTextToSearch = visibleText + " " + hiddenKeywords

            // 4. LA MAGIE : L'élément est valide SI ET SEULEMENT SI *tous* les mots recherchés s'y trouvent
            const matchesAllTerms = searchTerms.every(term => fullTextToSearch.includes(term))

            if (matchesAllTerms) {
                item.classList.remove("hidden")
                visibleCount++
            } else {
                item.classList.add("hidden")
            }
        })

        // 5. GESTION DE L'EMPTY STATE
        if (visibleCount === 0) {
            this.emptyTarget.classList.remove("hidden")
        } else {
            this.emptyTarget.classList.add("hidden")
        }
    }
}
