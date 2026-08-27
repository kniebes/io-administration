import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.currentModal = null;
    }

    open(event) {
        event.preventDefault();

        const modal = document.getElementById(event.currentTarget.dataset.modal);

        if (modal) {
            this.closeOnSubmit();
            modal.classList.add('show');
            this.currentModal = modal;
        }
    }

    close(event) {
        event.preventDefault();
        this.closeOnSubmit();
    }

    closeOnSubmit() {
        if (this.currentModal) {
            this.currentModal.classList.remove('show');
            this.currentModal = null;
        }
    }
}
