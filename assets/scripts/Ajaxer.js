import Flasher from './Flasher.js';
import Modal from './Modal.js';

let Ajaxer = (() => {

    /**
     * @param {(?FormData|URLSearchParams)} data
     * @param {string} method
     * @returns {RequestInit}
     */
    let buildRequest = (data, method) => {
        return {
            'method': method,
            'headers': {
                'X-Requested-With': 'XMLHttpRequest',
            },
            'credentials': 'same-origin',
            'redirect': 'follow',
            'referrerPolicy': 'origin-when-cross-origin',
            'body': data,
        };
    };

    /**
     * @param {string} method
     * @param {string} path
     * @param {FormData|URLSearchParams} data
     * @param {?Function} successCallback
     * @param {?Function} errorCallback
     * @returns {?Promise<string>}
     */
    let send = async (method, path, data = null, successCallback = null, errorCallback = null) => {

        /* global fetch */
        let response = await fetch(path, buildRequest(data, method));
        let html = await response.text();

        if(!response.ok) {
            if(errorCallback) {
                errorCallback();
            } else {
                console.error(`Error: ${response.statusText}`);
            }
        }

        if(response.redirected) {
            Modal.clear();
            window.location = response.url;
        }

        ['edit', 'delete/multiply'].every((item) => {
            if (!path.includes(item)) {
                window.history.pushState(
                    { route: path },
                    `Ajax Request: ${path}`,
                    path
                );
            }
        });

        successCallback(html);

        // Save flashes to enable them after the AJAX -> HTTP redirect.
        Flasher.save(Flasher.parseFlashes(html));

        return html;
    };

    /**
     * @param {string} path
     * @param {FormData} data
     * @param {?Function} successCallback
     * @param {?Function} errorCallback
     * @returns {Promise<void>}
     */
    let get = async (path, data = null, successCallback = null, errorCallback = null) => {
        await send('get', path, data, successCallback, errorCallback);
    };

    /**
     * @param {string} path
     * @param {FormData|URLSearchParams} data
     * @param {?Function} successCallback
     * @param {?Function} errorCallback
     */
    let post = async (path, data = null, successCallback = null, errorCallback = null) => {
        await send('post', path, data, successCallback, errorCallback);
    };

    return {
        get: get,
        post: post,
    };
})();

export default Ajaxer;
