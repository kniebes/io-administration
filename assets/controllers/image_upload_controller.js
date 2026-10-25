import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['area', 'input', 'list', 'editIcon'];
    static values = {
        url: String,
        csrfToken: String,
        uploadingLabel: String,
        editLabel: String,
        errorLabel: String,
        frameId: String,
    };

    open() {
        this.inputTarget.click();
    }

    dragOver(event) {
        event.preventDefault();
        this.areaTarget.classList.add('dragover');
    }

    dragLeave() {
        this.areaTarget.classList.remove('dragover');
    }

    drop(event) {
        event.preventDefault();
        this.areaTarget.classList.remove('dragover');
        this.upload(Array.from(event.dataTransfer.files));
    }

    select() {
        const files = Array.from(this.inputTarget.files);
        this.inputTarget.value = '';
        this.upload(files);
    }

    async upload(files) {
        if (files.length === 0) {
            return;
        }

        const rows = files.map((file) => this.buildRow(file.name));
        let hasSuccess = false;

        for (const [index, file] of files.entries()) {
            const isSuccess = await this.uploadFile(file, rows[index]);
            hasSuccess = hasSuccess || isSuccess;
        }

        if (hasSuccess) {
            this.reloadFrame();
        }
    }

    async uploadFile(file, row) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_csrf_token', this.csrfTokenValue);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            if (!response.ok) {
                this.markError(row, data.message ?? this.errorLabelValue);

                return false;
            }

            this.markDone(row, data.editUrl);

            return true;
        } catch {
            this.markError(row, this.errorLabelValue);

            return false;
        }
    }

    buildRow(fileName) {
        const row = document.createElement('li');
        const name = document.createElement('span');
        const status = document.createElement('span');

        name.className = 'upload-status__name';
        name.textContent = fileName;
        status.className = 'upload-status__state';
        status.textContent = this.uploadingLabelValue;

        row.append(name, status);
        this.listTarget.append(row);

        return row;
    }

    markDone(row, editUrl) {
        const status = row.querySelector('.upload-status__state');
        const link = document.createElement('a');

        link.href = editUrl;
        link.className = 'button';
        link.title = this.editLabelValue;
        link.setAttribute('aria-label', this.editLabelValue);
        link.dataset.turboFrame = '_top';
        link.append(this.editIconTarget.content.cloneNode(true));

        status.replaceChildren(link);
        row.classList.add('done');
    }

    markError(row, message) {
        row.querySelector('.upload-status__state').textContent = message;
        row.classList.add('error');
    }

    reloadFrame() {
        const frame = document.getElementById(this.frameIdValue);

        if (!frame) {
            return;
        }

        if (frame.src === window.location.href) {
            frame.reload();

            return;
        }

        frame.src = window.location.href;
    }
}
