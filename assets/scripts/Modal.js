import AjaxSender from './Ajaxer.js';
import Materializer from './Materializer.js';

let Modal = (() => {

    let Modal = {

        /** @returns {Element} */
        get elem() {
            return document.querySelector('.modal');
        },

        /** @returns {object} */
        get instance() {
            return M.Modal.getInstance(Modal.elem);
        },

        /** @returns {HTMLElement} */
        get listener() {
            return document.body;
        },

        get triggers() {
            return document.querySelectorAll('[data-target="materialize-modal"]');
        },

        /** @returns {Element} */
        get overlay() {
            return document.querySelector('.modal-overlay');
        },

        /** @returns {Element} */
        get form() {
            return document.querySelector('[data-role="modal-form"]');
        },

        /**
         * @param {MouseEvent} event
         * @returns {boolean}
         */
        isModalEvent: (event) => {
            return event.target.dataset.target === 'materialize-modal';
        },

        /** @param {string} html */
        appendContent: async (html) => {
            Modal.elem.innerHTML = html;
            Modal.overlay.addEventListener('click', Modal.clear);

            if(Modal.form !== null) {
                await Materializer.initSelects();
                Modal.form.addEventListener('submit', postForm);
            }
        },

        clear: () => {
            Modal.instance.close();
            Modal.elem.innerHTML = '';

            let path = sessionStorage.getItem('referrer');
            window.history.pushState(
                { route: path },
                `Ajax Request: ${path}`,
                path
            );

            if(Modal.form) { Modal.form.removeEventListener('submit', postForm); }
            if(Modal.overlay) { Modal.overlay.removeEventListener('click', Modal.clear); }
        },
    };

    /** @param {MouseEvent} event */
    let getForm = (event) => {
        if(Modal.isModalEvent(event)) {
            AjaxSender.get(event.target.href, null, Modal.appendContent);
        }
    };

    /** @param {MouseEvent} event */
    let postForm = (event) => {
        event.preventDefault();

        let data = new FormData(Modal.form);
        AjaxSender.post(Modal.form.action, data, Modal.appendContent);
    };

    let init = () => {
        if(Modal.triggers.length > 0) {
            Modal.listener.addEventListener('click', getForm);
        }
    };

    return {
        init: init,
        clear: Modal.clear,
    };
})();

export default Modal;
