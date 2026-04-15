import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'hiddenSelect', 'visibleSelect', 'submitBtn'];

    connect() {
        this.syncVisibleOptions();
        this.renderTags();
        this.updateButtonState();
    }

    add(event) {
        const select = event.target;
        const value = select.value;
        if (!value) return;

        const hiddenOption = Array.from(this.hiddenSelectTarget.options).find(opt => opt.value === value);

        if (hiddenOption && !hiddenOption.selected) {
            hiddenOption.selected = true;
            this.hiddenSelectTarget.dispatchEvent(new Event('change', { bubbles: true }));
            this.syncVisibleOptions();
            this.renderTags();
            this.updateButtonState();
        }
        select.value = "";
    }

    remove(event) {
        const valueToRemove = event.currentTarget.dataset.value;
        const hiddenOption = Array.from(this.hiddenSelectTarget.options).find(opt => opt.value === valueToRemove);

        if (hiddenOption) {
            hiddenOption.selected = false;
            this.hiddenSelectTarget.dispatchEvent(new Event('change', { bubbles: true }));
            this.syncVisibleOptions();
            this.renderTags();
            this.updateButtonState();
        }
    }

    updateButtonState() {
        const selectedCount = this.hiddenSelectTarget.selectedOptions.length;

        if (selectedCount === 0) {
            this.submitBtnTarget.disabled = true;
            this.submitBtnTarget.classList.add('opacity-50', 'cursor-not-allowed');
            this.submitBtnTarget.classList.remove('hover:bg-indigo-700', 'cursor-pointer');
        } else {
            this.submitBtnTarget.disabled = false;
            this.submitBtnTarget.classList.remove('opacity-50', 'cursor-not-allowed');
            this.submitBtnTarget.classList.add('hover:bg-indigo-700', 'cursor-pointer');
        }
    }

    syncVisibleOptions() {
        const selectedValues = Array.from(this.hiddenSelectTarget.selectedOptions).map(opt => opt.value);
        Array.from(this.visibleSelectTarget.options).forEach(option => {
            if (option.value !== "") {
                option.disabled = selectedValues.includes(option.value);
            }
        });
    }

    renderTags() {
        this.containerTarget.innerHTML = '';
        Array.from(this.hiddenSelectTarget.selectedOptions).forEach(option => {
            const tag = document.createElement('div');
            tag.className = "inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md shadow-sm";
            tag.innerHTML = `
                <span>${option.text}</span>
                <button type="button" data-action="click->email-tags#remove" data-value="${option.value}" class="text-indigo-400 hover:text-indigo-800 focus:outline-none transition-colors rounded-full p-0.5 hover:bg-indigo-200/50">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            this.containerTarget.appendChild(tag);
        });
    }
}
