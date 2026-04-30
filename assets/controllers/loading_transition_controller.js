import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // On déclare nos 3 éléments HTML cibles
    static targets = ['loader', 'loaderText', 'success'];

    connect() {
        // Au chargement de la page, on vérifie si l'animation a déjà été jouée
        if (sessionStorage.getItem('onboarding_animated') === 'true') {
            this.showInstantSuccess();
        } else {
            this.playAnimation();
        }
    }

    showInstantSuccess() {
        // Pas de chichi : on cache le loader et on affiche le succès direct
        this.loaderTarget.classList.add('hidden');

        // On enlève les classes qui le gardent invisible/réduit
        this.successTarget.classList.remove('hidden', 'opacity-0', 'scale-95', 'translate-y-4');
        this.successTarget.classList.add('opacity-100', 'scale-100', 'translate-y-0');
    }

    playAnimation() {
        // 1. Changement du texte intermédiaire
        setTimeout(() => {
            this.loaderTextTarget.style.opacity = '0';
            setTimeout(() => {
                this.loaderTextTarget.innerText = 'Application des politiques de conformité';
                this.loaderTextTarget.style.opacity = '1';
            }, 300);
        }, 1500);

        // 2. La grande transition fluide
        setTimeout(() => {
            // Étape A : On efface le loader
            this.loaderTarget.classList.remove('opacity-100', 'scale-100');
            this.loaderTarget.classList.add('opacity-0', 'scale-95');

            // Étape B : On croise les éléments (500ms correspondent au "duration-500" de Tailwind)
            setTimeout(() => {
                this.loaderTarget.classList.add('hidden');
                this.successTarget.classList.remove('hidden');

                // Étape C : Apparition fluide
                requestAnimationFrame(() => {
                    this.successTarget.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
                    this.successTarget.classList.add('opacity-100', 'scale-100', 'translate-y-0');
                });

                // 🎯 LE DÉTAIL CLÉ : On enregistre dans le navigateur que c'est fait !
                sessionStorage.setItem('onboarding_animated', 'true');

            }, 500);

        }, 2500);
    }
}
