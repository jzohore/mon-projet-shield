import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    static values = { topic: String }

    async connect() {
        if (!this.topicValue) return;

        this.component = await getComponent(this.element);
        this.eventSource = new EventSource(this.topicValue);

        this.eventSource.addEventListener('message', (event) => {
            //console.log("📥 [Mercure] Signal reçu !");
            this.component.action('refresh');
        });

        //console.log("🔌 [Mercure] Connecté au topic : " + this.topicValue);
    }

    disconnect() {
        if (this.eventSource) this.eventSource.close();
    }
}
