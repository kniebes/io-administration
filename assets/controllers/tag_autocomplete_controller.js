import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = { searchUrl: String, createUrl: String, csrfToken: String };

    connect() {
        this.tomSelect = new TomSelect(this.element, {
            valueField: 'value',
            labelField: 'text',
            searchField: [],
            create: (input, callback) => this.createTag(input, callback),
            load: (query, callback) => this.search(query, callback),
        });
    }

    disconnect() {
        this.tomSelect?.destroy();
    }

    search(query, callback) {
        fetch(`${this.searchUrlValue}?query=${encodeURIComponent(query)}`)
            .then((response) => response.json())
            .then((data) => callback(data.results))
            .catch(() => callback());
    }

    createTag(input, callback) {
        fetch(this.createUrlValue, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ term: input, _csrf_token: this.csrfTokenValue }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Tag konnte nicht angelegt werden (${response.status})`);
                }

                return response.json();
            })
            .then((data) => callback({ value: String(data.id), text: data.term }))
            .catch((error) => {
                console.error(error);
                callback();
            });
    }
}
