import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.resize();
    }

    resize() {
        this.element.style.height = '1px';
        this.element.style.height = `${this.element.scrollHeight}px`;
    }
}
