import { Controller } from '@hotwired/stimulus';
import RecordRTC from 'recordrtc'; // 🛡️ Import de la librairie ultra-robuste

export default class extends Controller {
    static values = {
        chunkUrl: String,
        stopUrl: String,
        maxMinutes: { type: Number, default: 0 }
    }
    static targets = ["indicator", "startBtn", "stopBtn", "pauseBtn", "resumeBtn", "timer"]

    recorder = null;
    microphone = null;
    uiTimerId = null;
    secondsRemaining = 0;
    chunkIndex = 0;
    pendingUploads = 0;

    async start() {
        if (this.recorder) return;
        if (this.maxMinutesValue <= 0) return alert("Solde de minutes épuisé.");

        try {
            // 1. Capture propre du micro
            this.microphone = await navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: true, noiseSuppression: true }
            });

            // 2. 🛡️ BOOTSTRAPPER : RecordRTC gère tous les bugs navigateurs (Safari, iOS, etc.)
            this.recorder = new RecordRTC(this.microphone, {
                type: 'audio',
                mimeType: 'audio/webm', // RecordRTC fera un fallback auto sur Safari (mp4/wav)
                recorderType: RecordRTC.StereoAudioRecorder, // Force un encodage stable
                disableLogs: true, // Clean en prod
                timeSlice: 10000, // Demande un chunk propre toutes les 10 secondes
                ondataavailable: (blob) => {
                    // Ce callback est appelé automatiquement et de manière 100% thread-safe
                    if (blob.size > 0) {
                        this.sendChunk(blob);
                    }
                }
            });

            // 3. Lancement
            this.recorder.startRecording();
            this.chunkIndex = 0;
            this.secondsRemaining = this.maxMinutesValue * 60;
            this.updateTimerUI();

            // 🚀 NOUVEAU : On prévient toute la page que l'écoute a commencé
            const startListeningBtn = document.getElementById('btn-start-listening');
            if (startListeningBtn) startListeningBtn.click();

            this.uiTimerId = setInterval(() => {
                if (this.recorder.getState() === 'paused') return;
                this.secondsRemaining--;
                this.updateTimerUI();

                if (this.secondsRemaining <= 0) this.stop();
            }, 1000);

            this.toggleUI('recording');

        } catch (err) {
            console.error("Erreur micro:", err);
            alert("Impossible d'accéder au microphone. Vérifiez vos autorisations.");
        }
    }

    pause() {
        if (this.recorder && this.recorder.getState() === 'recording') {
            this.recorder.pauseRecording();
            this.toggleUI('paused');
        }
    }

    resume() {
        if (this.recorder && this.recorder.getState() === 'paused') {
            this.recorder.resumeRecording();
            this.toggleUI('recording');
        }
    }

    async stop() {
        if (!this.recorder || this.recorder.getState() === 'stopped' || this.isStopping) return;

        this.isStopping = true;
        clearInterval(this.uiTimerId);
        this.timerTarget.innerHTML = "Enregistrement...";
        this.timerTarget.classList.add("text-indigo-600", "animate-pulse");

        this.recorder.stopRecording(async () => {
            if (this.microphone) {
                this.microphone.getTracks().forEach(track => track.stop());
                this.microphone = null;
            }

            // On attend la fin des uploads en cours
            await this.waitForPendingUploads();

            // 🔄 Restauration : Calcul précis du temps consommé
            const totalAllocatedSeconds = this.maxMinutesValue * 60;
            const consumedSeconds = totalAllocatedSeconds - Math.max(0, this.secondsRemaining);

            const formData = new FormData();
            formData.append('consumed_seconds', consumedSeconds.toString());

            try {
                // 🛡️ Envoi POST avec FormData ET les headers stricts pour Symfony
                const res = await fetch(this.stopUrlValue, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    throw new Error(data.error || `Erreur serveur (${res.status})`);
                }

                // 🚀 BOOTSTRAPPER FIX : On clique virtuellement sur le bouton caché pour lancer le polling
                const startPollingBtn = document.getElementById('btn-start-polling');
                if (startPollingBtn) {
                    startPollingBtn.click();
                }

            } catch (err) {
                alert(`Erreur: ${err.message}`);
                console.error("Stop Error:", err);
            } finally {
                this.recorder.destroy();
                this.recorder = null;
                this.toggleUI('idle');
            }
        });
    }
    async sendChunk(blob) {
        this.pendingUploads++;
        const formData = new FormData();
        // RecordRTC garantit l'extension et le format du blob
        formData.append('audio_chunk', blob, `chunk_${this.chunkIndex}.webm`);
        formData.append('chunk_index', this.chunkIndex++);

        try {
            const res = await fetch(this.chunkUrlValue, { method: 'POST', body: formData });
            if (res.status === 402 || res.status === 403) {
                this.stop(); // Stoppe net si Symfony hurle au quota dépassé
            }
        } catch (err) {
            console.error('Erreur réseau Chunk:', err);
        } finally {
            this.pendingUploads--;
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

    // --- Gestion de l'UI (Frugale) ---
    toggleUI(state) {
        if (this.hasStartBtnTarget) this.startBtnTarget.classList.toggle('hidden', state !== 'idle');
        if (this.hasPauseBtnTarget) this.pauseBtnTarget.classList.toggle('hidden', state !== 'recording');
        if (this.hasResumeBtnTarget) this.resumeBtnTarget.classList.toggle('hidden', state !== 'paused');
        if (this.hasStopBtnTarget) this.stopBtnTarget.classList.toggle('hidden', state === 'idle');

        if (this.hasIndicatorTarget) {
            this.indicatorTarget.classList.toggle('animate-pulse', state === 'recording');
            if (state === 'idle') this.indicatorTarget.classList.replace('bg-rose-500', 'bg-slate-300');
            if (state === 'paused') this.indicatorTarget.classList.replace('bg-rose-500', 'bg-amber-400');
        }
    }

    updateTimerUI() {
        const displaySeconds = Math.max(0, this.secondsRemaining);
        const m = Math.floor(displaySeconds / 60).toString().padStart(2, '0');
        const s = (displaySeconds % 60).toString().padStart(2, '0');

        this.timerTarget.innerHTML = `${m}:${s}`;
        this.timerTarget.classList.toggle('text-rose-600', displaySeconds > 0 && displaySeconds <= 300);
    }
}
