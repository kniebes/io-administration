import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

const CSRF_COOKIE_NAME = 'csrf-token';

export default class extends Controller {
    static values = { searchUrl: String, createUrl: String };

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
        const csrfToken = this.generateCsrfDoubleSubmitCookie();

        fetch(this.createUrlValue, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ term: input, _csrf_token: csrfToken }),
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

    // Same double-submit strategy as assets/controllers/csrf_protection_controller.js, adapted
    // for a plain fetch() call (which triggers no "submit" event to hook into).
    generateCsrfDoubleSubmitCookie() {
        const csrfToken = btoa(String.fromCharCode.apply(null, crypto.getRandomValues(new Uint8Array(18))));
        const cookie = `${CSRF_COOKIE_NAME}_${csrfToken}=${CSRF_COOKIE_NAME}; path=/; samesite=strict`;

        document.cookie = window.location.protocol === 'https:' ? `__Host-${cookie}; secure` : cookie;

        return csrfToken;
    }
}
