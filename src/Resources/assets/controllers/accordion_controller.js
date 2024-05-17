import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['header', 'content', 'arrow']
    static classes = ['arrowRotate', 'contentHidden']

    /**
     * @param {Event} event
     */
    toggle(event)
    {
        /** @type {HTMLElement[]} */
        const contentTargets = this.contentTargets;

        /** @type {HTMLElement[]} */
        const headerTargets = this.headerTargets;

        /** @type {HTMLElement[]} */
        const arrowTargets = this.arrowTargets;

        /** @type {string[]} */
        const arrowRotateClasses = this.arrowRotateClasses;

        /** @type {string[]} */
        const contentHiddenClasses = this.contentHiddenClasses;

        const current = event.currentTarget;
        const header = this.closestTarget(current, 'header');
        const arrow = this.closestTarget(current, 'arrow');
        const content = header.nextElementSibling;

        const isOpen = header.getAttribute('aria-expanded') === 'true';

        // Reset all accordions
        headerTargets.forEach((header) => {
            header.setAttribute('aria-expanded', 'false');
        });
        arrowTargets.forEach((arrow) => {
            arrow.classList.remove(...arrowRotateClasses);
        });
        contentTargets.forEach((content) => {
            content.classList.add(...contentHiddenClasses);
        });

        // Open the current accordion if it wasn't open before (else we toggle it)
        if (!isOpen) {
            arrow.classList.add(...arrowRotateClasses);
            header.setAttribute('aria-expanded', 'true');
            content.classList.remove(...contentHiddenClasses);
        }
    }

    /**
     * @param {HTMLElement} element
     * @param {string} target
     */
    closestTarget(element, target)
    {
        const childElement = element.closest(`[data-araise--table-bundle--accordion-target='${target}']`);
        if(childElement) {
            return childElement;
        }

        return element.parentElement;
    }
}

