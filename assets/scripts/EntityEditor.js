import AjaxSender from './Ajaxer.js';
import Flasher from './Flasher.js';
import Token from './Token.js';
import Translator from './Translator.js';

let EntityEditor = (() => {

    let Listener = {

        /** @returns {HTMLElement} document.body */
        get elem() {
            return document.body;
        },

        /**
         * @param {MouseEvent} event - Click
         * @param {HTMLElement} event.target - 'Enable editing' Action button
         * @returns {boolean}
         */
        isEnableEditEvent: (event) => {
            return event.target.getAttribute('data-role') === 'enable-editing';
        },

        /**
         * @param {MouseEvent} event - Click
         * @param {HTMLElement} event.target - 'Disable editing' Action button
         * @returns {boolean|boolean}
         */
        isDisableEditEvent: (event) => {
            return event.target.getAttribute('data-role') === 'disable-editing';
        },

        /**
         * @param {MouseEvent|FocusEvent} event - Click or Tab
         * @param {HTMLElement} event.target - Data Span
         * @returns {boolean}
         */
        isSpanStartEditEvent: (event) => {
            return event.type === 'focusin' && event.target.hasAttribute('contenteditable');
        },

        /**
         * @param {FocusEvent|KeyboardEvent} event - Blur or Enter pressed
         * @param {HTMLElement} event.target - Data Span
         * @returns {boolean}
         */
        isSpanStopEditEvent: (event) => {
            let isBlurFromDataCell = event.type === 'focusout' && event.target.hasAttribute('contenteditable');
            let isEnterOnDataCell = event.target.hasAttribute('contenteditable') && event.key === 'Enter';

            return isBlurFromDataCell || isEnterOnDataCell;
        },

        /**
         * @param {InputEvent|FocusEvent} event - Checkbox changed (Will fit to any Data input on the page)
         * @param {HTMLElement} event.target - Data Checkbox
         * @returns {boolean|boolean}
         */
        isCheckboxChangeEvent: (event) => {
            return event.target.localName === 'input'
                && event.target.hasAttribute('data-field');
        },

        setListeners: async () => {
            Listener.elem.addEventListener('click', State.setEnableEdit);
        },
    };

    let Router = {

        /**
         * @param {FocusEvent|KeyboardEvent|InputEvent} event - Blur or Enter on Data Span
         * @param {string} path [path=window.location.href]
         * @returns {string} - Should be http://localhost/cs/admin/user)/{id}/edit/{propertyName}
         */
        getEntityEditPath: (event, path = window.location.href) => {
            let params = Table.getEntityParams(event);

            let url = new URL(path);
            let parts = url.pathname.split('/').slice(1, 4).join('/');

            return `${url.origin}/${parts}/${params.entityId}/edit/${params.propertyName}`;
        },
    };

    let Validator = {

        /**
         * Creates dummy input to use Validation API with translated error messages.
         * It seems that HTML validation translates onto preferred language but not onto current locale?..
         * @param {object} params
         * @returns {{isValid: boolean, errorMessage: string}}
         */
        checkSpan: (params) => {
            let input = document.createElement('input');

            input.setAttribute('type', params.dataType);
            input.setAttribute('required', true);

            input.value = params.newValue;

            return {
                isValid: input.validity.valid,
                errorMessage: input.validationMessage,
            };
        },

        /**
         * @param {object} params
         * @returns {boolean}
         */
        checkCheckbox: (params) => {
            if(params.propertyName === 'roles') {
                return params.newValue === 'ROLE_USER' || params.newValue === 'ROLE_ADMIN';
            }
        },
    };

    let Table = {

        /** @returns {Element} - HTML Table */
        get elem() {
            return document.querySelector('[data-role="data-table"]');
        },

        /**
         * @param {FocusEvent|KeyboardEvent} event - Blur or `Enter`
         * @param {HTMLElement} event.target - Data Span
         * @returns {{entityId: string, propertyName: string}}
         */
        getEntityParams: (event) => {
            let row = event.target.closest('tr');

            return {
                entityId: row.querySelector('[data-content="id"]').innerText,
                propertyName: event.target.getAttribute('data-field'),
            }
        },

        enableDataFields: () => {
            // Should check when sort or paginate...
            if(State.isEnableEdit()) {
                DataSpan.setEnableEditAttrs();
                DataCheckbox.setEnableEditAttrs();
            }
        },

        disableDataFields: () => {
            DataSpan.setDisableEditAttrs();
            DataCheckbox.setDisableEditAttrs();
        },
    };

    let DataSpan = {

        /** @returns {NodeListOf<Element>} - All Data Spans from the current page */
        get elems() {
            return Table.elem.querySelectorAll('[data-field]:not(input)');
        },

        /**
         * @param {FocusEvent|KeyboardEvent} event - Blur or `Enter`
         * @param {HTMLElement} event.target - Data Span
         * @returns {{entityId: string, propertyName: string, newValue: string, oldValue: string, dataType: string}}
         */
        getParams: (event) => {
            return {...{
                    newValue: event.target.innerText,
                    oldValue: event.target.getAttribute('data-old'),
                    dataType: event.target.getAttribute('data-type'),
                }, ...Table.getEntityParams(event)};
        },

        /** Set 'contenteditable' and 'tabindex' to the Data Spans */
        setEnableEditAttrs: () => {
            [...DataSpan.elems].forEach((elem) => {
                elem.setAttribute('contenteditable', true);
                elem.setAttribute('tabindex', '0');
            });
        },

        /** Remove 'contenteditable' and 'tabindex' from the Data Spans */
        setDisableEditAttrs: () => {
            [...DataSpan.elems].forEach((elem) => {
                elem.removeAttribute('contenteditable');
                elem.removeAttribute('tabindex');
            });
        },

        /**
         * @param {KeyboardEvent} event - Enter
         * @param {HTMLElement} event.target - Data Span
         */
        onEnterPress: (event) => {
            if(Listener.isSpanStopEditEvent(event)) {
                event.preventDefault();
                sendSpanValue(event);
            }
        },
    };

    let DataCheckbox = {

        /** @returns {NodeListOf<Element>} */
        get elems() {
            return document.querySelectorAll('input[data-field][type="checkbox"]');
        },

        /**
         * @param {InputEvent|FocusEvent} event
         * @param {HTMLElement} event.target
         * @returns {boolean}
         */
        isRolesField: (event) => {
            return event.target.getAttribute('data-field') === 'roles';
        },

        /**
         * @param {InputEvent|FocusEvent} event
         * @param {HTMLElement} event.target
         * @returns {string}
         */
        getRoleAsString: (event) => {
            return event.target.checked ? 'ROLE_ADMIN' : 'ROLE_USER';
        },

        /**
         * @param {InputEvent|FocusEvent|KeyboardEvent} event - Blur, `Enter` or Change
         * @param {HTMLElement} event.target
         * @returns {{entityId: string, propertyName: string, newValue: string, oldValue: string, dataType: string}}
         */
        getParams: (event) => {
            let currentValue = '';
            let oldValue = '';

            if(DataCheckbox.isRolesField(event)) {
                currentValue = DataCheckbox.getRoleAsString(event);
                oldValue = event.target.getAttribute('data-old');
            }

            return {...{
                newValue: currentValue,
                oldValue: oldValue,
                dataType: event.target.getAttribute('type'),
            }, ...Table.getEntityParams(event)};
        },

        /** Remove 'disabled' from the Data Checkboxes */
        setEnableEditAttrs: () => {
            [...DataCheckbox.elems].forEach((elem) => {
                elem.removeAttribute('disabled');
            });
        },

        /** Set 'disabled' to the Data Checkboxes */
        setDisableEditAttrs: () => {
            if(State.isEnableEdit()) {
                [...DataCheckbox.elems].forEach((elem) => {
                    elem.setAttribute('disabled', true);
                });
            }
        },
    };

    let Actions = {

        /** @returns {Element} - Actions container button */
        get elem() {
            return document.querySelector('.fixed-action-btn');
        },

        enableEditButton: {

            /** @returns {Element} - 'Enable edit' action button */
            get elem() {
                return Actions.elem.querySelector('[data-role="enable-editing"]');
            },

            /** display: 'inline-block' */
            show: () => {
                Actions.enableEditButton.elem.classList.remove('hide');
            },

            /** display: 'none' */
            hide: () => {
                Actions.enableEditButton.elem.classList.add('hide');
            },
        },

        disableEditButton: {

            /** @returns {Element} - 'Disable edit' action button */
            get elem() {
                return Actions.elem.querySelector('[data-role="disable-editing"]');
            },

            /** display: 'inline-block' */
            show: () => {
                Actions.disableEditButton.elem.classList.remove('hide');
            },

            /** is display: 'inline-block'? */
            isShown: () => {
                return !Actions.disableEditButton.elem.classList.contains('hide');
            },

            /** display: 'none' */
            hide: () => {
                Actions.disableEditButton.elem.classList.add('hide');
            },
        },
    };

    let State = {

        /** @returns {boolean} - Is 'Disable edit' button on the page now? */
        isEnableEdit: () => {
            return Actions.disableEditButton.isShown();
        },

        /** @param {MouseEvent} event - Click `Enable edit` button */
        setEnableEdit: (event) => {
            if(Listener.isEnableEditEvent(event)) {
                Actions.enableEditButton.hide();
                Actions.disableEditButton.show();

                Listener.elem.removeEventListener('click', State.setEnableEdit);

                Listener.elem.addEventListener('click', State.setDisableEdit);
                Listener.elem.addEventListener('focusin', State.startSpanEditing);
                Listener.elem.addEventListener('change', sendCheckboxValue);

                Table.enableDataFields();
                let message = Translator.trans('You can edit users directly in the table');
                Flasher.sendTexts([message], 'info');
            }
        },

        /** @param {MouseEvent} event - Click `Disable edit` button */
        setDisableEdit: (event) => {
            if(Listener.isDisableEditEvent(event)) {
                Table.disableDataFields();

                Actions.disableEditButton.hide();
                Actions.enableEditButton.show();

                let message = Translator.trans('Editing disabled');
                Flasher.sendTexts([message], 'info');

                Listener.elem.addEventListener('click', State.setEnableEdit);

                Listener.elem.removeEventListener('click', State.setDisableEdit);
                Listener.elem.removeEventListener('focusin', State.startSpanEditing);
                Listener.elem.removeEventListener('focusout', sendSpanValue);
                Listener.elem.removeEventListener('keydown', DataSpan.onEnterPress);
                Listener.elem.removeEventListener('change', sendCheckboxValue);
            }
        },

        /**
         * @param {FocusEvent|KeyboardEvent} event - Click or Tab press
         * @param {HTMLElement} event.target - Data Span
         */
        startSpanEditing: (event) => {
            if(Listener.isSpanStartEditEvent(event)) {
                event.target.setAttribute('data-focus', null);

                Listener.elem.addEventListener('keydown', DataSpan.onEnterPress);
                Listener.elem.addEventListener('focusout', sendSpanValue);

                Flasher.clear();
            }
        },
    }

    /**
     * Configure data and send it to Symfony
     * @param {FocusEvent|KeyboardEvent} event - Blur or Enter
     * @param {HTMLElement} event.target - Data Span
     */
    let sendSpanValue = (event) => {
        if(Listener.isSpanStopEditEvent(event)) {
            let params = DataSpan.getParams(event);

            let result = Validator.checkSpan(params);

            if(!result.isValid) {
                Flasher.sendTexts([result.errorMessage], 'error');
                event.target.innerText = params.oldValue;
            } else if(params.oldValue !== params.newValue) {
                let data = new FormData();
                data.append(params.propertyName, params.newValue);
                data.append(Token.csrfRequestKey, Token.csrfToken);

                AjaxSender.post(Router.getEntityEditPath(event), data, appendValue);
            }
        }
    };

    /**
     * @param {InputEvent} event - Change
     * @param {HTMLElement} event.target - Checkbox
     */
    let sendCheckboxValue = (event) => {
        if(Listener.isCheckboxChangeEvent(event)) {
            let params = DataCheckbox.getParams(event);
            event.target.setAttribute('data-focus', null);

            let isValid = Validator.checkCheckbox(params);

            if(!isValid) {
                Flasher.sendTexts(['Please, choose the right role'], 'error');
                event.target.checked = params.oldValue;
            } else {
                let data = new FormData();
                data.append(params.propertyName, params.newValue);
                data.append(Token.csrfRequestKey, Token.csrfToken);

                AjaxSender.post(Router.getEntityEditPath(event), data, appendValue);
            }
        }
    };

    /**
     * Get JSON Response and insert data into the Table.
     * @param {string} jsonData
     */
    let appendValue = (jsonData) => {
        let target = document.querySelector('[data-focus]');

        let data = JSON.parse(jsonData);

        if(data.errors) {
            Flasher.sendTexts(data.errors);
        } else {
            let property = Translator.trans(data.property);
            let message = Translator.trans('The property was updated successfully', {'property': property});

            Flasher.sendTexts([message], 'success');
        }

        target.removeAttribute('data-focus');

        if(data.property === 'roles') {
            target.setAttribute('data-old',data.value);
            // The correct value comes from the server in any case
        } else {
            target.setAttribute('data-old', data.value);
            target.innerText = data.value;
        }
    };

    return {
        init: Listener.setListeners,
        setEnableEditAttrs: Table.enableDataFields,
    };
})();

export default EntityEditor;
