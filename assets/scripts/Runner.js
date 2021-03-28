import Flasher from './Flasher.js';
import Materializer from './Materializer.js';
import Modal from './Modal.js';
import Page from './Page.js';

let Runner = (() => {

    let run = () => {
        // Save every loaded page, but not Ajax
        sessionStorage.setItem('referrer', window.location.href);

        Materializer.autoInit();

        Flasher.init();
        Modal.init();
        Page.init();
    }

    return {
        run: run,
    }
})();

export default Runner;
