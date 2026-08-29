import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list', 'item'];
    static values = { index: Number, prototype: String };

    add(event) {
        event.preventDefault();

        const newItem = this.prototypeValue.replace(/__name__/g, String(this.indexValue));

        this.listTarget.insertAdjacentHTML('beforeend', newItem);
        this.indexValue += 1;
    }

    remove(event) {
        event.preventDefault();

        event.target.closest('[data-collection-target~="item"]').remove();
    }
}
