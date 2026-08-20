import { Controller } from '@hotwired/stimulus';
import RecordRTC from 'recordrtc'; // 🛡️ Import de la librairie ultra-robuste

export default class extends Controller {
    static values = {
        chunkUrl: String,
        stopUrl: String,
        maxMinutes: { type: Number, default: 0 }
    }
    static targets = ["indicator", "startBtn", "stopBtn", "pauseBtn", "resumeBtn", "timer"]

    // 🛡️ Déclaration explicite de toutes les propriétés de classe
    recorder = null;
    microphone = null;
    uiTimerId = null;
    secondsRemaining = 0;
    chunkIndex = 0;
    pendingUploads = 0;
    isStopping = false;

    // Analyse Audio (Fiabilité matérielle)
    audioContext = null;
    analyser = null;
    dataArray = null;
    audioProcessingInterval = null;
    silenceTimer = null;

    async start() {
        if (this.recorder || this.isStopping) return;
        if (this.maxMinutesValue <= 0) return alert("Solde de minutes épuisé.");

        try {
            // 1. Capture propre du micro (🛡️ FIX : noiseSuppression à false pour éviter les coupures de voix)
            this.microphone = await navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: true, noiseSuppression: false }
            });

            // 2. 🛡️ Sécurité Matérielle : Détection de la perte du micro (ex: Airpods déconnectés)
            const audioTrack = this.microphone.getAudioTracks()[0];
            audioTrack.addEventListener('ended', () => this.handleHardwareError("Microphone déconnecté."));
            audioTrack.addEventListener('mute', () => this.handleHardwareError("Microphone muté par le système."));

            // 3. Lancement de l'analyse audio (VU-Mètre + Silence)
            this.startAudioAnalysis();

            // 4. 🛡️ BOOTSTRAPPER FIX : Retrait de StereoAudioRecorder. On laisse RecordRTC utiliser MediaStreamRecorder natif.
            this.recorder = new RecordRTC(this.microphone, {
                type: 'audio',
                mimeType: 'audio/webm', // Fallback natif géré par le navigateur (ex: Safari -> mp4)
                disableLogs: true, // Clean en prod
                timeSlice: 10000, // Demande un chunk propre toutes les 10 secondes
                ondataavailable: (blob) => {
                    if (blob.size > 0 && !this.isStopping) {
                        this.sendChunk(blob);
                    }
                }
            });

            // 5. Lancement
            this.recorder.startRecording();
            this.chunkIndex = 0;
            this.secondsRemaining = this.maxMinutesValue * 60;
            this.updateTimerUI();

            // 🚀 BOOTSTRAPPER FIX : Le clic fantôme est exécuté APRES la garantie de succès de RecordRTC
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
            alert("Impossible d'accéder au microphone. Vérifiez vos autorisations système.");
        }
    }

    pause() {
        if (this.recorder && this.recorder.getState() === 'recording') {
            this.recorder.pauseRecording();
            this.toggleUI('paused');
            // 🚀 Émission de l'événement local de pause
            window.dispatchEvent(new CustomEvent('kysure:recording:paused'));
        }
    }

    resume() {
        if (this.recorder && this.recorder.getState() === 'paused') {
            this.recorder.resumeRecording();
            this.toggleUI('recording');
            // 🚀 Émission de l'événement local de reprise
            window.dispatchEvent(new CustomEvent('kysure:recording:resumed'));
        }
    }

    async stop() {
        if (!this.recorder || this.recorder.getState() === 'stopped' || this.isStopping) return;

        this.isStopping = true;
        clearInterval(this.uiTimerId);
        this.stopAudioAnalysis();

        this.timerTarget.innerHTML = "Enregistrement...";
        this.timerTarget.classList.add("text-indigo-600", "animate-pulse");

        this.recorder.stopRecording(async () => {
            if (this.microphone) {
                this.microphone.getTracks().forEach(track => track.stop());
                this.microphone = null;
            }

            // On attend la fin des uploads en cours
            await this.waitForPendingUploads();

            // Calcul précis du temps consommé
            const totalAllocatedSeconds = this.maxMinutesValue * 60;
            const consumedSeconds = totalAllocatedSeconds - Math.max(0, this.secondsRemaining);

            const formData = new FormData();
            formData.append('consumed_seconds', consumedSeconds.toString());

            try {
                // Envoi POST final
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

                // 🚀 Déclenchement du LiveComponent pour afficher l'analyse IA
                const startPollingBtn = document.getElementById('btn-start-polling');
                if (startPollingBtn) {
                    startPollingBtn.click();
                }

            } catch (err) {
                alert(`Erreur: ${err.message}`);
                console.error("Stop Error:", err);
            } finally {
                // 🛡️ FIX : Vérification stricte avant destruction pour éviter une double exception
                if (this.recorder) {
                    try {
                        this.recorder.destroy();
                    } catch (e) {
                        console.warn("Erreur silencieuse lors de la destruction du recorder:", e);
                    }
                    this.recorder = null;
                }
                this.isStopping = false;
                this.timerTarget.classList.remove("text-indigo-600", "animate-pulse");
                this.updateTimerUI();
                this.toggleUI('idle');
                window.dispatchEvent(new CustomEvent('kysure:recording:stopped'));
            }
        });
    }

    async sendChunk(blob) {
        this.pendingUploads++;
        const formData = new FormData();

        // 🛡️ FIX : Récupération dynamique du mime_type réel (et gestion du fallback Safari)
        const actualMimeType = blob.type || 'audio/webm';

        // 🛡️ FIX : Déduction logique de l'extension de fichier
        let extension = 'webm';
        if (actualMimeType.includes('mp4')) extension = 'mp4';
        else if (actualMimeType.includes('wav') || actualMimeType.includes('wave')) extension = 'wav';
        else if (actualMimeType.includes('ogg')) extension = 'ogg';

        formData.append('audio_chunk', blob, `chunk_${this.chunkIndex}.${extension}`);
        formData.append('chunk_index', this.chunkIndex++);
        formData.append('mime_type', actualMimeType); // 🛡️ FIX : Crucial pour le FFmpeg backend

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

    // --- Sécurité Matérielle & UI ---

    handleHardwareError(message) {
        console.error(message);
        alert(`Attention : ${message} L'enregistrement est compromis.`);
        if (this.recorder && this.recorder.getState() === 'recording') {
            this.pause(); // On met en pause pour laisser le temps au CGP de rebrancher
        }
    }

    startAudioAnalysis() {
        // Frugalité : on initialise le contexte uniquement quand nécessaire
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return; // Sécurité si le navigateur est très vieux

        this.audioContext = new AudioContext();
        const source = this.audioContext.createMediaStreamSource(this.microphone);
        this.analyser = this.audioContext.createAnalyser();

        this.analyser.fftSize = 256;
        source.connect(this.analyser);
        this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);

        this.audioProcessingInterval = setInterval(() => {
            if (this.recorder && this.recorder.getState() === 'paused') return;

            this.analyser.getByteFrequencyData(this.dataArray);
            let sum = 0;
            for (let i = 0; i < this.dataArray.length; i++) sum += this.dataArray[i];
            const average = sum / this.dataArray.length;

            // Détection de silence prolongé (seuil arbitraire très bas)
            if (average < 2) {
                if (!this.silenceTimer) {
                    this.silenceTimer = setTimeout(() => {
                        console.warn("Silence détecté : Le micro ne capte aucun son.");
                        // Optionnel : tu pourrais déclencher une Toast Notification ici
                    }, 10000); // 10 secondes de silence continu
                }
            } else {
                if (this.silenceTimer) {
                    clearTimeout(this.silenceTimer);
                    this.silenceTimer = null;
                }
            }
        }, 200);
    }

    stopAudioAnalysis() {
        if (this.audioProcessingInterval) clearInterval(this.audioProcessingInterval);
        if (this.silenceTimer) clearTimeout(this.silenceTimer);
        if (this.audioContext && this.audioContext.state !== 'closed') {
            this.audioContext.close().catch(console.error);
        }
        this.audioContext = null;
        this.analyser = null;
    }

    // --- Gestion de l'UI ---
    toggleUI(state) {
        if (this.hasStartBtnTarget) this.startBtnTarget.classList.toggle('hidden', state !== 'idle');
        if (this.hasPauseBtnTarget) this.pauseBtnTarget.classList.toggle('hidden', state !== 'recording');
        if (this.hasResumeBtnTarget) this.resumeBtnTarget.classList.toggle('hidden', state !== 'paused');
        if (this.hasStopBtnTarget) this.stopBtnTarget.classList.toggle('hidden', state === 'idle');

        if (this.hasIndicatorTarget) {
            this.indicatorTarget.classList.toggle('hidden', state !== 'recording');
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
