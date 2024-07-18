import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['header', 'content', 'arrow']
    static classes = ['arrowRotate', 'contentHidden']

    connect() {
        /** @type {HTMLElement[]} */
        const headerTargets = this.headerTargets;

        headerTargets.forEach((header) => {
            const isOpenDefault = header.getAttribute('aria-expanded') === 'true';
            const contents = this.getAccordionContents(header);
            const arrow = this.closestChildTarget(header, 'arrow');

            if(!arrow) {
                return;
            }

            // Open all accordions on load. This can be definied in araise (OPT_SUB_TABLE_COLLAPSED)
            if (isOpenDefault) {
                this.applyOpenState({arrow, contents});
            }
        });
    }

    /**
     * @param {Event} event
     */
    toggle(event)
    {
        const current = event.currentTarget;
        const header = this.closestTarget(current, 'header');
        const arrow = this.closestTarget(current, 'arrow');
        const contents = this.getAccordionContents(header);

        const isOpen = header.getAttribute('aria-expanded') === 'true';

        // Open the current accordion if it wasn't open before (else we toggle it)
        if (!isOpen) {
            this.applyOpenState({arrow, contents, header});
        } else {
            this.applyCloseState({arrow, contents, header});
        }
    }

    /**
     * @param {HTMLElement[]} elements
     */
    applyOpenState(elements) {
        const {arrow, contents, header} = elements;

        /** @type {string[]} */
        const arrowRotateClasses = this.arrowRotateClasses;

        /** @type {string[]} */
        const contentHiddenClasses = this.contentHiddenClasses;

        header && header.setAttribute('aria-expanded', 'true');
        arrow.classList.add(...arrowRotateClasses);
        contents.forEach((item) => {
            item.classList.remove(...contentHiddenClasses);
        });
    }

    /**
     * @param {HTMLElement[]} elements
     */
    applyCloseState(elements) {
        const {arrow, contents, header} = elements;

        /** @type {string[]} */
        const arrowRotateClasses = this.arrowRotateClasses;

        /** @type {string[]} */
        const contentHiddenClasses = this.contentHiddenClasses;

        header && header.setAttribute('aria-expanded', 'false');
        arrow.classList.remove(...arrowRotateClasses);
        contents.forEach((item) => {
            item.classList.add(...contentHiddenClasses);
        });
    }

    /**
     * @param {HTMLElement} header
     * @return {HTMLElement[]}
     *
     * The content block can output subtables. Those can be defined in the definition of araise.
     * Multiple subtables can be defined, so we want to toggle them in groups together.
     * Those are children of the header and not wrapped inside a div, that's why we return an array of elements.
     */
    getAccordionContents(header) {
        return this.getNextSiblingsWithClass(header, 'whatwedo_table-subtable');
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

    /**
     * @param {HTMLElement} element
     * @param {string} className
     */
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

