import {Controller} from '@hotwired/stimulus';
import 'regenerator-runtime/runtime'

export default class extends Controller {

    static targets = ['ids', 'selector', 'checkAll', 'unCheckAll', 'selectedCount']
    static values = {
        footSelectedTemplate: String
    }
    static classes = ['hideCount']

    connect() {
        if (!this.hasIdsTarget) {
            return;
        }
        this.idsTarget.value = '[]';
        this.selectorTargets.forEach(selector => {
            selector.checked = false;
        });
    }

    /**
     * @param {Event} event
     */
    selectId(event) {
        if (!event.target.dataset.entityId) {
            return;
        }
        const eventId = event.target.dataset.entityId;
        const ids = this.getIds();

        if (ids.includes(eventId)) {
            this.removeId(eventId);
            return;
        }

        this.addId(eventId);
    }

    tableTargetChanged() {
        this.syncSelectedIds();
        this.updateSelectedCount();
    }

    checkAll() {
        this.selectorTargets.forEach(selector => {
            if (selector.checked) {
                return;
            }
            this.addId(selector.dataset.entityId);
            selector.checked = true;
        });
        this.checkAllTarget.classList.add(this.hideCountClasses);
        this.unCheckAllTarget.classList.remove(this.hideCountClasses);
    }

    unCheckAll() {
        this.selectorTargets.forEach(selector => {
            this.removeId(selector.dataset.entityId);
            selector.checked = false;
        });
        this.checkAllTarget.classList.remove(this.hideCountClasses);
        this.unCheckAllTarget.classList.add(this.hideCountClasses);
    }

    /**
     * @param {string} id
     */
    hasId(id) {
        let ids = this.getIds();
        return ids.includes(id)
    }

    /**
     * @param {string} id
     */
    addId(id) {
        let ids = this.getIds();
        ids.push(id);
        this.idsTarget.value = JSON.stringify(ids);
        this.updateSelectedCount();
    }

    /**
     * @param {string} id
     */
    removeId(id) {
        let ids = this.getIds();
        ids = ids.filter(function (value, index, arr) {
            return value != id;
        })
        this.idsTarget.value = JSON.stringify(ids);
        this.updateSelectedCount();
        if (ids.length == 0) {
            this.checkAllTarget.classList.remove(this.hideCountClasses);
            this.unCheckAllTarget.classList.add(this.hideCountClasses);
        }
    }

    updateSelectedCount() {
        const count = this.getIds().length;

        if(this.hasSelectedCountTarget) {
            if (count === 0) {
                this.selectedCountTarget.classList.add(this.hideCountClasses);
                return;
            }

            this.selectedCountTarget.classList.remove(this.hideCountClasses);
            this.selectedCountTarget.innerHTML = this.footSelectedTemplateValue.replace('{count}', count);
        }
    }

    syncSelectedIds() {
        let ids = this.getIds();
        this.selectorTargets.forEach(selector => {
            selector.checked = ids.includes(selector.dataset.entityId);
        });

    }

    openUrl(url, openType, openWidth, openHeight) {
        if (openType === 'window') {
            const width = openWidth || window.innerWidth;
            const height = openHeight || window.innerHeight;
            window.open(url, undefined, `width=${width}px,height=${height}px`);
        } else {
            window.open(url, '_blank');
        }
    }

    async doAction(event) {
        event.preventDefault();

        let ids = this.getIds();
        if (ids.length == 0) {
            alert('no selection');
            return;
        }
        let formData = new FormData;
        ids.forEach(id => {
            formData.append('ids[]', id);
        });
        await fetch(event.target.getAttribute('href'), {
            method: 'POST',
            body: formData
        }) .then(async response => {
            if (response.status == 200) {
                let data = await response.json();
                if (data.reload) {
                    window.location.reload();
                }
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
                if (data.open && Array.isArray(data.open)) {
                    data.open.forEach(item => {
                        this.openUrl(item.url, item.openType, item.openWidth, item.openHeight);
                    });
                }
                if (data.url) {
                    window.location.href = data.url;
                }
                if (!data.reload && !data.redirect && !data.open) {
                    console.warn('Batch Action Controller should return json data with reload or redirect information');
                }
            } else {
                alert('error. please try again');
            }
        });
    }



    getIds() {
        return JSON.parse(this.idsTarget.value);
    }

}
