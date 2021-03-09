import M from '@materializecss/materialize';

let Materializer = (() => {

    let autoInit = () => {
        M.AutoInit();
    };

    return {
        autoInit: autoInit,
    }
})();

export default Materializer;
