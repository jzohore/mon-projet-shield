// assets/controllers/chat_widget_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['iconOpen', 'iconClose'];

    connect() {
        if (localStorage.getItem('chat_opened') === 'true') {
            this.open();
        }
    }

    toggle() {
        document.body.classList.contains('kysure-chat-open') ? this.close() : this.open();
    }

    open() {
        // 🛡️ FIX : L'état est géré au niveau du body, Morphdom ne peut pas le casser !
        document.body.classList.add('kysure-chat-open');
        this.iconOpenTarget.classList.add('hidden');
        this.iconCloseTarget.classList.remove('hidden');
        localStorage.setItem('chat_opened', 'true');
    }

    close() {
        document.body.classList.remove('kysure-chat-open');
        this.iconOpenTarget.classList.remove('hidden');
        this.iconCloseTarget.classList.add('hidden');
        localStorage.setItem('chat_opened', 'false');
    }
}
