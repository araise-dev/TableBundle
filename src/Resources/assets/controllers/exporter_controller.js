import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['link']

    select(event) {
        document.querySelectorAll('input[name="exporter"]').forEach((checkbox) => {
            if (checkbox !== event.target) {
                checkbox.checked = false;
            }
        });

        this.linkTargets.forEach((link) => {
            const url = new URL(link.href);
            url.searchParams.set('exporter', event.target.value);
            link.href = url.toString();
        });
    }
}
