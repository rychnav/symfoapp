let Flasher = (() => {

    /** @type {number} - Delay between the Flash messages in queue */
    const DEFAULT_DURATION = 1000;

    let FlashParser = {

        /**
         * @param {string} htmlString
         * @returns {NodeListOf<Element>}
         */
        fromHtml: (htmlString) => {
            let doc = new DOMParser().parseFromString(htmlString, "text/html");
            return doc.querySelectorAll('[data-role="flash-message"]');
        },

        /**
         * @param {NodeListOf<Element>} flashes
         * @returns {string}
         */
        toHtml: (flashes) => {
            let html = '';

            if(flashes.length > 0) {
                flashes.forEach((flash) => {
                    html += flash.outerHTML;
                });
            }

            return html;
        },
    };

    let FlashSender = {

        /** @param {Element} flash */
        sendOne: (flash) => {
            M.toast({
                html: flash.innerHTML,
                classes: `sym-${Message.getType(flash)}-message`,
            });
        },

        /**
         * @param {int} duration
         * @param {HTMLCollection} flashes
         */
        sendMany: (flashes, duration=DEFAULT_DURATION) => {
            if(flashes && flashes.length > 0) {
                /** Every next message will be sent after the one second from the previous one */
                Array.from(flashes).forEach((flash, i) => {
                    setTimeout(() => { FlashSender.sendOne(flash); }, i * duration);
                });
            }
        },

        sendAll: async () => {
            await Storage.export();
            await FlashContainer.send();

            Storage.clear();
        },

        /**
         * @param {array<string>} messagesText
         * @param {string} messageType
         */
        sendTexts: (messagesText, messageType='error') => {
            FlashContainer.clear();

            // We should append flashes before the send them because of Flash viewer will not working.
            messagesText.forEach((text) => {
                FlashContainer.elem.innerHTML += Message.createFromText(text, messageType);
            });

            FlashContainer.send();
            FlashViewer.showButton();
        },
    };

    let Listener = {
        setEvents: () => {
            if(FlashViewer.elem) {
                FlashViewer.elem.addEventListener('click', FlashSender.sendAll);
            }
        },
    };

    let FlashContainer = {

        /** @returns {Element} */
        get elem() {
            return document.querySelector(
                '[data-role="flash-container"]'
            );
        },

        /** @returns {HTMLCollection} */
        get flashes() {
            return FlashContainer.elem.children;
        },

        /** @returns {boolean} */
        hasFlashes: () => {
            return FlashContainer.flashes.length > 0;
        },

        send: () => {
            let messages = FlashContainer.flashes;

            if(messages.length > 0) {
                FlashSender.sendMany(messages);
            }
        },

        clear: () => {
            FlashContainer.elem.innerHTML = '';
            FlashViewer.hideButton();
        },
    };

    let Message = {

        /** @returns {string} */
        get selector() {
            return '[data-role="flash-message"]';
        },

        /**
         * @param {Element} elem
         * @returns {string}
         */
        getType: (elem) => {
            let className = elem.classList[elem.classList.length - 1];
            return className.match("-(.*)-")[1];
        },

        /**
         * Wrap Flash  message in HTML tags.
         * @param {string} messageText
         * @param {string} messageType
         * @returns {string}
         */
        createFromText: (messageText, messageType) => {
            return `<div class="sym-${messageType}-message" data-role="flash-message">
                <svg class="sym-icon__small__light"><use xlink:href="#sym-${messageType}"></use></svg>
                <span class="sym-flash-text">${messageText}</span>
            </div>`;
        },
    };

    let Storage = {
        /** @type {string} key */
        key: 'flashes',

        /** @returns {boolean} */
        has: () => {
            return Storage.get() !== '';
        },

        /** @returns {string|null} */
        get: () => {
            return window.sessionStorage.getItem(Storage.key);
        },

        /** @param {NodeListOf<Element>} elems - Flash messages elems */
        save: (elems) => {
            if(elems) {
                window.sessionStorage.setItem(Storage.key, FlashParser.toHtml(elems));
            }
        },

        export: () => {
            if(Storage.has()) {
                FlashContainer.elem.insertAdjacentHTML('beforeend', Storage.get());
            }
        },

        clear: () => {
            window.sessionStorage.removeItem(Storage.key);
        },
    };

    let FlashViewer = {

        /** @returns {Element} */
        get elem() {
            return document.querySelector('[data-role="flash-viewer-button"]');
        },

        showButton: () => {
            FlashViewer.elem.classList.remove('hide');
        },

        hideButton: () => {
            FlashViewer.elem.classList.add('hide');
        },
    };

    let init = () => {
        Listener.setEvents();
        FlashSender.sendAll()
            .catch(err => {console.log(err)});

        if(FlashContainer.hasFlashes()) {
            FlashViewer.showButton();
        }
    }

    return {
        clear: FlashContainer.clear,
        init: init,
        parseFlashes: FlashParser.fromHtml,
        save: Storage.save,
        sendTexts: FlashSender.sendTexts,
    };
})();

export default Flasher;
