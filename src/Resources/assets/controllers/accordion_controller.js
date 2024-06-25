import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['header', 'content', 'arrow']
    static classes = ['arrowRotate', 'contentHidden']

    initialize() {
        super.initialize();

        /** @type {HTMLElement[]} */
        const contentTargets = this.contentTargets;

        /** @type {HTMLElement[]} */
        const headerTargets = this.headerTargets;

        /** @type {string[]} */
        const arrowRotateClasses = this.arrowRotateClasses;

        /** @type {string[]} */
        const contentHiddenClasses = this.contentHiddenClasses;

        headerTargets.forEach((header) => {
            const isOpen = header.getAttribute('aria-expanded') === 'true';
            const content = header.nextElementSibling;
            const arrow = this.closestChildTarget(header, 'arrow');

            if(!arrow) {
                return;
            }

            if (isOpen) {
                arrow.classList.add(...arrowRotateClasses);
                content.classList.remove(...contentHiddenClasses);
            } else {
                arrow.classList.remove(...arrowRotateClasses);
                content.classList.add(...contentHiddenClasses);
            }
        });
    }

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

        // Open the current accordion if it wasn't open before (else we toggle it)
        if (!isOpen) {
            arrow.classList.add(...arrowRotateClasses);
            header.setAttribute('aria-expanded', 'true');
            content.classList.remove(...contentHiddenClasses);
        } else {
            arrow.classList.remove(...arrowRotateClasses);
            header.setAttribute('aria-expanded', 'false');
            content.classList.add(...contentHiddenClasses);
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


    /**
     * @param {HTMLElement|null} element
     * @param {string} target
     */
    closestChildTarget(element, target) {
        const childElement = element.querySelector(`[data-araise--table-bundle--accordion-target='${target}']`);
        if(childElement) {
            return childElement;
        }

        return null;
    }
}

