import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['status', 'blog', 'blogPostType', 'hint'];

    connect() {
        this.update();
    }

    update() {
        const isPublishable = this.blogTarget.value !== '' && this.blogPostTypeTarget.value !== '';
        const isPublished = this.statusTarget.value === 'published';
        const publishedOption = this.statusTarget.querySelector('option[value="published"]');
        const isLocked = !isPublishable && !isPublished;

        if (publishedOption) {
            publishedOption.disabled = isLocked;
        }

        this.hintTarget.hidden = !isLocked;
    }
}
