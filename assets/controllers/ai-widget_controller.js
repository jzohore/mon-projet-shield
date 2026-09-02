import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content', 'chevron'];
    static values = { key: String };

    connect() {
        this.isCollapsed = localStorage.getItem(this.storageKey) === 'true';

        // 1. On coupe les transitions pendant le calcul initial pour éviter que ça "saute"
        this.element.style.transition = 'none';
        this.contentTarget.style.transition = 'none';

        // 2. On applique la bonne dimension instantanément (invisiblement)
        this.applyState(false);

        // 3. Bouclier Anti-FOUC : On restaure les transitions et on fait apparaître le widget
        requestAnimationFrame(() => {
            this.element.style.transition = '';
            this.contentTarget.style.transition = '';
            // On retire les classes d'invisibilité initiales
            this.element.classList.remove('opacity-0', 'translate-y-2', 'pointer-events-none');
            this.element.classList.add('opacity-100', 'translate-y-0');
        });

        this.handlePause = () => this.setPausedState(true);
        this.handleResume = () => this.setPausedState(false);
        window.addEventListener('kysure:recording:paused', this.handlePause);
        window.addEventListener('kysure:recording:resumed', this.handleResume);
    }

    disconnect() {
        // Toujours nettoyer les events listeners
        window.removeEventListener('kysure:recording:paused', this.handlePause);
        window.removeEventListener('kysure:recording:resumed', this.handleResume);
    }

    toggle(event) {
        this.isCollapsed = !this.isCollapsed;
        localStorage.setItem(this.storageKey, this.isCollapsed);
        this.applyState(true);
    }

    applyState(animate = true) {
        if (!animate) {
            this.contentTarget.style.transition = 'none';
            this.element.style.transition = 'none';
            if (this.hasChevronTarget) this.chevronTarget.style.transition = 'none';
        }

        if (this.isCollapsed) {
            // ÉTAT FERMÉ : Widget compact (300px)
            this.contentTarget.style.maxHeight = '0px';
            this.element.classList.remove('w-[92vw]', 'sm:w-[440px]');
            this.element.classList.add('w-[300px]');
            if (this.hasChevronTarget) this.chevronTarget.classList.remove('-rotate-180');
        } else {
            // ÉTAT OUVERT : Side-panel (440px max)
            this.contentTarget.style.maxHeight = '75vh';
            this.element.classList.remove('w-[300px]');
            this.element.classList.add('w-[92vw]', 'sm:w-[440px]');
            if (this.hasChevronTarget) this.chevronTarget.classList.add('-rotate-180');
        }

        if (!animate) {
            this.element.offsetHeight; // Force reflow du navigateur
            setTimeout(() => {
                this.contentTarget.style.transition = '';
                this.element.style.transition = '';
                if (this.hasChevronTarget) this.chevronTarget.style.transition = '';
            }, 50);
        }
    }

    setPausedState(isPaused) {
        if (this.hasPulseTarget) {
            this.pulseTarget.classList.toggle('animate-ping', !isPaused);
            this.pulseTarget.classList.toggle('bg-rose-100', !isPaused);
            this.pulseTarget.classList.toggle('bg-amber-100', isPaused);
        }
        if (this.hasStatusTextTarget) {
            this.statusTextTarget.innerText = isPaused ? 'Enregistrement en pause' : "L'IA vous écoute...";
        }
        if (this.hasHeaderTextTarget) {
            this.headerTextTarget.innerText = isPaused ? 'Capture en pause' : 'Capture audio active';
        }
    }

    get storageKey() {
        return `kysure_ai_widget_${this.keyValue}`;
    }
}
