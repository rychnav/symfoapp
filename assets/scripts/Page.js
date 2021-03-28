import Ajaxer from './Ajaxer.js';
import Materializer from './Materializer.js';

let Page = (() => {

    let Container = {
        get elem() {
            return document.querySelector('.sym-content');
        },

        /**
         * @param {string} html
         * @returns {HTMLElement}
         */
        extractFromHtml: (html) => {
            let doc = new DOMParser().parseFromString(html, "text/html");
            return doc.querySelector('.sym-content');
        },

        /** @param {string} html */
        appendContent: (html) => {
            let content = Container.extractFromHtml(html);

            Container.elem.replaceWith(content);
            Materializer.initTooltips();
        }
    };

    let Listener = {

        /** @returns {HTMLElement} */
        get elem() {
            return document.body;
        },

        /**
         * @param {MouseEvent} event
         * @param {HTMLElement} event.target
         * @returns boolean
         */
        isAjaxEvent: (event) => {
            return event.target.getAttribute('data-role') === 'sort-link'
                || event.target.getAttribute('data-role') === 'pagination-link';
        },

        setEvents: () => {
            Listener.elem.addEventListener('click', sendGet);
        },
    };

    /**
     * @param {MouseEvent} event
     * @param {HTMLElement} event.target
     */
    let sendGet = (event) => {
        if(event && Listener.isAjaxEvent(event)) {
            event.preventDefault();

            let path = event.target.href;
            Ajaxer.get(path, null, Container.appendContent);
        }
    };

    return {
        init: Listener.setEvents,
    };
})();

export default Page;
