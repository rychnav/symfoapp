import Materializer from './Materializer.js';
import Modal from './Modal.js';

let Runner = (() => {

    let run = () => {
        // Save every loaded page, but not Ajax
        sessionStorage.setItem('referrer', window.location.href);

        Materializer.autoInit();
        Modal.init();
    }

    return {
        run: run,
    }
})();

export default Runner;
