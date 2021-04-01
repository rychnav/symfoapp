import Ajaxer from './Ajaxer.js';
import Page from './Page.js';
import Token from './Token';

let Chooser = (() => {

    let DomHelper = {

        /**
         * @param {boolean} shouldBeChecked
         * @returns {HTMLLabelElement}
         */
        createChoosebox: (shouldBeChecked = false) => {
            let checkbox = document.createElement('input');
            checkbox.setAttribute('type', 'checkbox');
            checkbox.setAttribute('data-role', 'choose-entity');

            if(shouldBeChecked) {
                checkbox.checked = true;
            }

            let span = document.createElement('span');

            let label = document.createElement('label');
            label.appendChild(checkbox);
            label.appendChild(span);

            return label;
        },
    };

    let Listener = {

        /** @returns {HTMLElement} document.body */
        get elem() {
            return document.body;
        },

        /**
         * @param {MouseEvent|KeyboardEvent} event
         * @param {HTMLElement} event.target
         * @returns {boolean}
         */
        isChooseboxCheckEvent: (event) => {
            return event.target.getAttribute('data-role') === 'choose-entity';
        },

        /**
         * @param {MouseEvent} event
         * @param {HTMLElement} event.target
         * @returns {boolean}
         */
        isShowChooseboxesEvent: (event) => {
            return event.target.getAttribute('data-role') === 'show-chooseboxes';
        },

        /**
         * @param {MouseEvent} event
         * @param {HTMLElement} event.target
         * @returns {boolean}
         */
        isHideChooseboxesEvent: (event) => {
            return event.target.getAttribute('data-role') === 'hide-chooseboxes';
        },

        /**
         * @param {MouseEvent} event
         * @param {HTMLElement} event.target
         * @returns {boolean}
         */
        isRemoveManyEvent: (event) => {
            return event.target.getAttribute('data-role') === 'remove-entities';
        },

        setListeners: () => {
            if(Table.elem) {
                Listener.elem.addEventListener('click', showChooseboxes);
                Listener.elem.addEventListener('click', saveId);
            }
        }
    };

    let Router = {

        /**
         * @param {MouseEvent} event
         * @param {string} path
         * @returns {string}
         */
        getRemoveManyPath: (event, path = window.location.href) => {
            let url = new URL(path);
            let parts = url.pathname.split('/').slice(1, 4).join('/');

            return `${url.origin}/${parts}/delete/multiply`;
        },
    };

    let Storage = {

        /** @type {string} key */
        key: 'remove_multiply',

        /** @returns {array} */
        getIds: () => {
            return JSON.parse(window.sessionStorage.getItem(Storage.key)) || [];
        },

        /**
         * @param {array<string>} ids
         */
        setIds: (ids) => {
            window.sessionStorage.setItem(Storage.key, JSON.stringify(ids));
        },

        removeIds: () => {
            window.sessionStorage.removeItem(Storage.key);
        },

        /** @param {string} id */
        saveId: (id) => {
            let ids = Storage.getIds();

            if(ids.includes(id)) {
                return;
            }

            ids.push(id);
            Storage.setIds(ids);
        },

        /** @param {string} id */
        removeId: (id) => {
            let ids = Storage.getIds();

            if(ids.includes(id)) {
                ids.splice(ids.indexOf(id), 1);
            }

            Storage.setIds(ids);
        },
    };

    let Table = {

        /** @returns {Element} */
        get elem() {
            return document.querySelector('[data-role="data-table"]');
        },

        /** @returns {NodeListOf<Element>} */
        get chooseboxes() {
            return Table.elem.querySelectorAll('[data-content="id"] ~ label input');
        },

        /** @returns {NodeListOf<Element>} */
        get idSpans() {
            return Table.elem.querySelectorAll('[data-content="id"]');
        },

        /** @returns {HTMLTableRowElement[]} */
        get rowsChosen() {
            return [...Table.elem.rows].filter(row => {
                let checkbox = row.querySelector('[data-content="id"] ~ label input');

                if(checkbox) {
                    return checkbox.checked;
                }
            });
        },

        /**
         * @param {HTMLElement|Element|EventTarget} elem
         * @returns {string}
         */
        getClosestId: (elem) => {
            return elem.closest('td').querySelector('[data-content="id"]').innerText.trim();
        },

        /** @returns {boolean} */
        hasSomeChooseboxChosen: () => {
            return [...Table.chooseboxes].some(checkbox => checkbox.checked);
        },

        setChooseboxes: () => {
            let checkedIds = Storage.getIds();

            Table.idSpans.forEach((idSpan) => {
                let id = Table.getClosestId(idSpan);
                // Check choosebox if the the checkbox was saved on previous pages.
                let checkbox = DomHelper.createChoosebox(checkedIds.includes(id));

                idSpan.style.fontSize = '0';
                idSpan.closest('td').appendChild(checkbox);
            });
        },

        removeChooseboxes: () => {
            Table.idSpans.forEach((idSpan) => {
                let label = idSpan.closest('td').querySelector('label');

                label.parentNode.removeChild(label);
                idSpan.style.removeProperty('font-size');
            });
        },
    };

    let Actions = {

        /** @returns {Element} */
        get elem() {
            return document.querySelector('.fixed-action-btn');
        },

        showChooseboxesButton: {
            /**  @returns {Element} */
            get elem() {
                return document.querySelector('[data-role="show-chooseboxes"]');
            },

            hide: () => {
                Actions.showChooseboxesButton.elem.classList.add('hide');
            },

            show: () => {
                Actions.showChooseboxesButton.elem.classList.remove('hide');
            },
        },

        hideChooseboxesButton: {
            /**  @returns {Element} */
            get elem() {
                return document.querySelector('[data-role="hide-chooseboxes"]');
            },

            /** @returns {boolean} */
            isShown: () => {
                let hideButton = Actions.hideChooseboxesButton.elem;

                return hideButton && !hideButton.classList.contains('hide');
            },

            hide: () => {
                Actions.hideChooseboxesButton.elem.classList.add('hide');
            },

            show: () => {
                Actions.hideChooseboxesButton.elem.classList.remove('hide');
            },
        },

        removeManyButton: {
            /** @returns {Element} */
            get elem() {
                return Actions.elem.querySelector('[data-role="remove-entities"]');
            },

            hide: () => {
                Actions.removeManyButton.elem.classList.add('hide');
            },

            show: () => {
                Actions.removeManyButton.elem.classList.remove('hide');
            },

            /** @param {MouseEvent|null} event */
            toggle: (event) => {
                if(Listener.isChooseboxCheckEvent(event) || Listener.isShowChooseboxesEvent(event)) {
                    if(Table.hasSomeChooseboxChosen()) {
                        Actions.removeManyButton.show();
                        Listener.elem.addEventListener('click', removeEntities);
                    } else {
                        Actions.removeManyButton.hide();
                        Listener.elem.removeEventListener('click', removeEntities);
                    }
                }
            },
        },
    };

    /** @param {MouseEvent} event */
    let showChooseboxes = (event) => {
        if(Listener.isShowChooseboxesEvent(event)) {
            Table.setChooseboxes();

            Listener.elem.removeEventListener('click', showChooseboxes);
            Listener.elem.addEventListener('click', hideChooseboxes);

            Actions.showChooseboxesButton.hide();
            Actions.hideChooseboxesButton.show();

            Actions.removeManyButton.toggle(event);

            Listener.elem.addEventListener('change', Actions.removeManyButton.toggle);
        }
    };

    /** @param {MouseEvent} event */
    let hideChooseboxes = (event) => {
        if(Listener.isHideChooseboxesEvent(event)) {
            Table.removeChooseboxes();

            Listener.elem.addEventListener('click', showChooseboxes);
            Listener.elem.removeEventListener('click', hideChooseboxes);

            Actions.showChooseboxesButton.show();
            Actions.hideChooseboxesButton.hide();

            Actions.removeManyButton.hide();
            Storage.removeIds();
        }
    };

    /**
     * @param {MouseEvent} event
     * @param {HTMLElement} event.target
     */
    let saveId = (event) => {
        if(Listener.isChooseboxCheckEvent(event)) {
            let id = Table.getClosestId(event.target);

            if(event.target.checked) {
                Storage.saveId(id);
            } else {
                Storage.removeId(id);
            }

        }
    };

    /** @param {MouseEvent} event */
    let removeEntities = (event) => {
        if(Listener.isRemoveManyEvent(event)) {
            let data = new URLSearchParams();
            data.append('ids', JSON.stringify(Storage.getIds()));
            data.append(Token.csrfRequestKey, Token.csrfToken);

            Ajaxer.post(Router.getRemoveManyPath(event), data, Page.appendPageContent);
            Storage.removeIds();
        }
    };

    let init = () => {
        Listener.setListeners();

        if(Actions.hideChooseboxesButton.isShown()) {
            Table.setChooseboxes();
        }
    };

    return {
        init: init,
    }
})();

export default Chooser;
