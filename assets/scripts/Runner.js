import Materializer from './Materializer.js';

let Runner = (() => {

    let run = () => {
        Materializer.autoInit();
    }

    return {
        run: run,
    }
})();

export default Runner;
