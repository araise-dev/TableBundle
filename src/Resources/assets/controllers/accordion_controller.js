import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['header', 'content', 'arrow']
    static classes = ['arrowRotate', 'contentHidden']

    initialize() {
        super.initialize();

        /** @type {HTMLElement[]} */
        const headerTargets = this.headerTargets;

        /** @type {string[]} */
        const arrowRotateClasses = this.arrowRotateClasses;

        /** @type {string[]} */
        const contentHiddenClasses = this.contentHiddenClasses;

        headerTargets.forEach((header) => {
            const isOpen = header.getAttribute('aria-expanded') === 'true';
            // Subtables can be defined in the definition. It the content block outputs them, we can handle them
            const contents = this.getNextSiblingsWithClass(header, 'whatwedo_table-subtable');
            const arrow = this.closestChildTarget(header, 'arrow');

            if(!arrow) {
                return;
            }

            if (isOpen) {
                arrow.classList.add(...arrowRotateClasses);
                contents.forEach((subtable) => {
                    subtable.classList.remove(...contentHiddenClasses);
                });
            } else {
                arrow.classList.remove(...arrowRotateClasses);
                contents.forEach((subtable) => {
                    subtable.classList.add(...contentHiddenClasses);
                });
            }
        });
    }

    /**
     * @param {Event} event
     */
    toggle(event)
    {
        /** @type {string[]} */
        const arrowRotateClasses = this.arrowRotateClasses;

        /** @type {string[]} */
        const contentHiddenClasses = this.contentHiddenClasses;

        const current = event.currentTarget;
        const header = this.closestTarget(current, 'header');
        const arrow = this.closestTarget(current, 'arrow');
        // Subtables can be defined in the definition. It the content block outputs them, we can toggle them
        const contents = this.getNextSiblingsWithClass(header, 'whatwedo_table-subtable');

        const isOpen = header.getAttribute('aria-expanded') === 'true';

        // Open the current accordion if it wasn't open before (else we toggle it)
        if (!isOpen) {
            arrow.classList.add(...arrowRotateClasses);
            header.setAttribute('aria-expanded', 'true');
            contents.forEach((subtable) => {
                subtable.classList.remove(...contentHiddenClasses);
            });
        } else {
            arrow.classList.remove(...arrowRotateClasses);
            header.setAttribute('aria-expanded', 'false');
            contents.forEach((subtable) => {
                subtable.classList.add(...contentHiddenClasses);
            });
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

    getNextSiblingsWithClass(element, className) {
        let siblings = [];
        let next = element.nextElementSibling;

        while (next && next.classList.contains(className)) {
            siblings.push(next);
            next = next.nextElementSibling;
        }

        return siblings;
    }
}

