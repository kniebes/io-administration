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
    }
}
