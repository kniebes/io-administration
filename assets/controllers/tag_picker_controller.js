import { Controller } from '@hotwired/stimulus';

const MINIMUM_QUERY_LENGTH = 2;
const SEARCH_DELAY = 200;

export default class extends Controller {
    static targets = ['input', 'suggestions', 'list'];
    static values = { index: Number, prototype: String, searchUrl: String, createLabel: String };

    disconnect() {
        clearTimeout(this.searchTimeout);
    }

    search() {
        clearTimeout(this.searchTimeout);

        const query = this.inputTarget.value.trim();

        if (query.length < MINIMUM_QUERY_LENGTH) {
            this.clearSuggestions();

            return;
        }

        this.searchTimeout = setTimeout(() => this.loadSuggestions(query), SEARCH_DELAY);
    }

    loadSuggestions(query) {
        fetch(`${this.searchUrlValue}?query=${encodeURIComponent(query)}`)
            .then((response) => response.json())
            .then((tags) => this.renderSuggestions(query, tags))
            .catch(() => this.clearSuggestions());
    }

    renderSuggestions(query, tags) {
        if (this.inputTarget.value.trim() !== query) {
            return;
        }

        this.suggestionsTarget.replaceChildren();

        tags.forEach((tag) => {
            this.suggestionsTarget.append(this.buildSuggestion(String(tag.id), tag.term, false));
        });

        const hasExactMatch = tags.some((tag) => tag.term.toLowerCase() === query.toLowerCase());

        if (!hasExactMatch) {
            this.suggestionsTarget.append(this.buildSuggestion(query, query, true));
        }
    }

    buildSuggestion(value, label, isCreate) {
        const suggestion = document.createElement('button');

        suggestion.type = 'button';
        suggestion.className = isCreate ? 'tag-picker__suggestion create' : 'tag-picker__suggestion';
        suggestion.dataset.value = value;
        suggestion.dataset.label = label;
        suggestion.dataset.action = 'tag-picker#select';
        suggestion.textContent = isCreate ? this.createLabelValue.replace('%term%', label) : label;

        return suggestion;
    }

    select(event) {
        const suggestion = event.currentTarget;

        this.addRow(suggestion.dataset.value, suggestion.dataset.label);
        this.inputTarget.value = '';
        this.clearSuggestions();
        this.inputTarget.focus();
    }

    keydown(event) {
        if (event.key === 'Escape') {
            this.clearSuggestions();

            return;
        }

        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        const first = this.suggestionsTarget.firstElementChild;

        if (first) {
            first.click();
        }
    }

    remove(event) {
        event.preventDefault();
        event.target.closest('[data-tag-picker-target="item"]').remove();
        this.reindex();
    }

    reindex() {
        const items = this.listTarget.querySelectorAll('[data-tag-picker-target="item"]');

        items.forEach((item, index) => {
            const input = item.querySelector('input[type="hidden"]');

            input.name = input.name.replace(/\[tags]\[\d+]/, `[tags][${index}]`);
            input.id = input.id.replace(/_tags_\d+/, `_tags_${index}`);
        });

        this.indexValue = items.length;
    }

    addRow(value, label) {
        const alreadySelected = Array.from(this.listTarget.querySelectorAll('input[type="hidden"]')).some(
            (input) => input.value === value,
        );

        if (alreadySelected) {
            return;
        }

        const newItem = this.prototypeValue.replace(/__name__/g, String(this.indexValue));

        this.listTarget.insertAdjacentHTML('beforeend', newItem);

        const item = this.listTarget.lastElementChild;

        item.querySelector('input[type="hidden"]').value = value;
        item.querySelector('[data-tag-picker-target="itemLabel"]').textContent = label;

        this.reindex();
    }

    clearSuggestions() {
        this.suggestionsTarget.replaceChildren();
    }
}
