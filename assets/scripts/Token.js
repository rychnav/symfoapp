let Token = (() => {

    let CSRF = {

        /**
         * @methodOf CSRF
         * @returns {string}
         */
        get requestKey() {
            return 'csrf_token';
        },

        /**
         * @methodOf CSRF
         * @returns {string}
         */
        get token() {
            let input = document.querySelector('[data-role="csrf-input"]');
            return input ? input.value : null ;
        }
    };

    return {
        csrfRequestKey: CSRF.requestKey,
        csrfToken: CSRF.token,
    }
})();

export default Token;
