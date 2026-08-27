import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['picker', 'list'];
    static values = { index: Number, prototype: String };

    add() {
        const option = this.pickerTarget.selectedOptions[0];

        if (!option || !option.value) {
            return;
        }

        const newItem = this.prototypeValue.replace(/__name__/g, String(this.indexValue));

        this.listTarget.insertAdjacentHTML('beforeend', newItem);
        this.indexValue += 1;

        const item = this.listTarget.lastElementChild;
        const select = item.querySelector('select');
        const label = item.querySelector('[data-images-picker-target="itemLabel"]');

        select.value = option.value;
        label.textContent = option.dataset.url;

        this.pickerTarget.value = '';
    }

    remove(event) {
        event.preventDefault();
        event.target.closest('[data-images-picker-target="item"]').remove();
        this.reindex();
    }

    dragStart(event) {
        this.draggedItem = event.currentTarget;
        event.dataTransfer.effectAllowed = 'move';
        this.draggedItem.classList.add('dragging');
    }

    dragOver(event) {
        event.preventDefault();

        const target = event.currentTarget;

        if (!this.draggedItem || target === this.draggedItem) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const isAfter = event.clientY - rect.top > rect.height / 2;

        target.parentElement.insertBefore(this.draggedItem, isAfter ? target.nextSibling : target);
    }

    drop(event) {
        event.preventDefault();
    }

    dragEnd() {
        if (this.draggedItem) {
            this.draggedItem.classList.remove('dragging');
            this.draggedItem = null;
        }

        this.reindex();
    }

    // Renumbers every row's hidden <select> name/id sequentially to match the current
    // DOM order. Without this, the submitted array keys would still reflect the
    // original render order, not a reorder done purely via drag & drop in the browser.
    reindex() {
        const items = this.listTarget.querySelectorAll('[data-images-picker-target="item"]');

        items.forEach((item, index) => {
            const select = item.querySelector('select');

            select.name = select.name.replace(/\[images]\[\d+]/, `[images][${index}]`);
            select.id = select.id.replace(/_images_\d+/, `_images_${index}`);
        });

        this.indexValue = items.length;
    }
}
