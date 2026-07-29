import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        chunkUrl: String,
        stopUrl: String,
        maxMinutes: { type: Number, default: 0 }
    }
    static targets = ["indicator", "startBtn", "stopBtn", "pauseBtn", "resumeBtn", "timer"]

    mediaRecorder = null;
    chunkIntervalId = null;
    uiTimerId = null;
    secondsRemaining = 0;
    pendingUploads = 0;
    isPaused = false;
    isStopping = false;
    chunkIndex = 0;
    mimeType = 'audio/webm';

    async start() {
        if (this.mediaRecorder) return; // évite un double-start accidentel

        if (this.maxMinutesValue <= 0) {
            alert("Opération impossible : Le solde de minutes de votre cabinet est épuisé.");
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

            // 🛡️ Fallback cross-browser : webm n'est pas supporté par Safari
            this.mimeType = this.pickSupportedMimeType();
            this.mediaRecorder = new MediaRecorder(stream, { mimeType: this.mimeType });

            this.chunkIndex = 0;
            this.isStopping = false;

            this.mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.sendChunk(e.data);
            };

            this.mediaRecorder.start();
            this.isPaused = false;

            this.secondsRemaining = this.maxMinutesValue * 60;
            this.updateTimerUI();

            this.uiTimerId = setInterval(() => {
                if (this.isPaused) return;

                this.secondsRemaining--;
                this.updateTimerUI();

                if (this.secondsRemaining <= 0) this.stop();
            }, 1000);

            this.chunkIntervalId = setInterval(() => {
                if (!this.isPaused && this.mediaRecorder.state === "recording") {
                    this.mediaRecorder.requestData();
                }
            }, 10000);

            this.toggleUI('recording');

        } catch (err) {
            alert("Erreur micro : Vérifiez les autorisations.");
        }
    }

    pause() {
        if (!this.mediaRecorder || this.mediaRecorder.state !== "recording") return;
        this.mediaRecorder.pause();
        this.isPaused = true;
        this.mediaRecorder.requestData();
        this.toggleUI('paused');
    }

    resume() {
        if (!this.mediaRecorder || this.mediaRecorder.state !== "paused") return;
        this.mediaRecorder.resume();
        this.isPaused = false;
        this.toggleUI('recording');
    }

    async stop() {
        if (this.isStopping) return; // empêche un double-appel (timer à 0 + clic manuel)
        if (!this.mediaRecorder || (this.mediaRecorder.state !== "recording" && this.mediaRecorder.state !== "paused")) return;

        this.isStopping = true;

        clearInterval(this.uiTimerId);
        clearInterval(this.chunkIntervalId);

        this.timerTarget.innerHTML = "Sauvegarde...";
        this.timerTarget.classList.add("text-amber-500", "animate-pulse");

        // 🪄 Calcul du temps réel consommé (Temps de départ - Temps restant)
        const totalAllocatedSeconds = this.maxMinutesValue * 60;
        const consumedSeconds = totalAllocatedSeconds - this.secondsRemaining;

        const recorder = this.mediaRecorder;
        const stream = recorder.stream;

        // 🛡️ FIX RACE CONDITION : on attend l'événement natif "stop" du MediaRecorder,
        // qui ne se déclenche qu'APRÈS que le dernier "dataavailable" ait été traité
        // (donc APRÈS que sendChunk() ait incrémenté pendingUploads).
        // Avant ce fix, le polling démarrait immédiatement et pouvait voir
        // pendingUploads === 0 alors que le dernier chunk n'avait pas encore été envoyé.
        const waitForFinalChunk = new Promise((resolve) => {
            recorder.addEventListener('stop', () => resolve(), { once: true });
        });

        if (recorder.state === "paused") {
            // stop() peut être appelé directement depuis l'état "paused",
            // pas besoin de resume() avant (ça évite un aller-retour d'état inutile)
        }
        recorder.stop();
        stream.getTracks().forEach(t => t.stop());

        await waitForFinalChunk;

        // Maintenant on peut attendre en toute sécurité que tous les uploads
        // (y compris le tout dernier) soient terminés
        await this.waitForPendingUploads();

        try {
            const formData = new FormData();
            formData.append('consumed_seconds', consumedSeconds);

            const res = await fetch(this.stopUrlValue, {
                method: 'POST',
                body: formData
            });

            if (!res.ok) {
                throw new Error(`Statut HTTP ${res.status}`);
            }

            const liveComponent = document.querySelector('[data-live-name-value="compliance_ai_report"]');
            if (liveComponent) {
                liveComponent.dispatchEvent(new CustomEvent('meeting:stopped'));
            }
        } catch (err) {
            alert("Erreur lors de la finalisation de l'enregistrement. Veuillez réessayer ou contacter le support.");
        } finally {
            this.mediaRecorder = null;
            this.isStopping = false;
            this.toggleUI('idle');
        }
    }

    waitForPendingUploads() {
        return new Promise((resolve) => {
            const check = setInterval(() => {
                if (this.pendingUploads === 0) {
                    clearInterval(check);
                    resolve();
                }
            }, 200);
        });
    }

    async sendChunk(blob) {
        this.pendingUploads++;
        const formData = new FormData();
        formData.append('audio_chunk', blob);
        formData.append('chunk_index', this.chunkIndex++);
        // 🛡️ Le backend doit savoir quel conteneur a réellement été utilisé
        // (Safari enregistre en audio/mp4, pas en audio/webm) pour traiter
        // et remuxer le fichier correctement.
        formData.append('mime_type', this.mimeType);

        try {
            const res = await fetch(this.chunkUrlValue, { method: 'POST', body: formData });
            if (res.status === 402 || res.status === 403) {
                alert("Quota dépassé.");
                this.stop();
            } else if (!res.ok) {
                // 🛡️ Chunk perdu : on prévient au lieu d'échouer silencieusement
                console.error(`Échec d'envoi du chunk ${formData.get('chunk_index')} : HTTP ${res.status}`);
            }
        } catch (err) {
            console.error('Erreur réseau lors de l\'envoi du chunk audio :', err);
        } finally {
            this.pendingUploads--;
        }
    }

    pickSupportedMimeType() {
        const candidates = ['audio/webm', 'audio/mp4', 'audio/ogg'];
        for (const type of candidates) {
            if (MediaRecorder.isTypeSupported(type)) return type;
        }
        return ''; // laisse le navigateur choisir par défaut
    }

    toggleUI(state) {
        // state = 'idle' | 'recording' | 'paused'

        if (this.hasStartBtnTarget) {
            this.startBtnTarget.classList.toggle('hidden', state !== 'idle');
        }

        if (this.hasPauseBtnTarget) {
            this.pauseBtnTarget.classList.toggle('hidden', state !== 'recording');
        }

        if (this.hasResumeBtnTarget) {
            this.resumeBtnTarget.classList.toggle('hidden', state !== 'paused');
        }

        if (this.hasStopBtnTarget) {
            this.stopBtnTarget.classList.toggle('hidden', state === 'idle');
        }

        if (this.hasIndicatorTarget) {
            this.indicatorTarget.classList.toggle('animate-pulse', state === 'recording');
            this.indicatorTarget.classList.toggle('text-red-500', state === 'recording');
        }

        if (this.hasTimerTarget) {
            if (state === 'paused') {
                this.timerTarget.classList.add('text-amber-500', 'animate-pulse');
                this.timerTarget.classList.remove('text-slate-700', 'text-red-500');
            } else if (state === 'recording') {
                this.timerTarget.classList.remove('animate-pulse', 'text-amber-500');
            }
        }
    }

    updateTimerUI() {
        const displaySeconds = Math.max(0, this.secondsRemaining);
        const m = Math.floor(displaySeconds / 60).toString().padStart(2, '0');
        const s = (displaySeconds % 60).toString().padStart(2, '0');
        const timeString = `${m}:${s}`;

        this.timerTarget.innerHTML = timeString;

        if (!this.isPaused) {
            const isCritical = displaySeconds > 0 && displaySeconds <= 300;
            if (isCritical) {
                this.timerTarget.classList.add('text-red-500');
            } else {
                this.timerTarget.classList.remove('text-red-500');
            }
        }

        const externalTimer = document.getElementById('external-timer-display');
        if (externalTimer) externalTimer.innerHTML = timeString;
    }
}
