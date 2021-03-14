import M from '@materializecss/materialize';

let Materializer = (() => {

    let autoInit = () => {
        M.AutoInit();

        initDropdowns();
    };

    /** Initiate modules separately to pass extra params */

    let initDropdowns = () => {
        let dropdowns = document.querySelectorAll('.dropdown-trigger');
        M.Dropdown.init(dropdowns, { constrainWidth: false });
    };

    return {
        autoInit: autoInit,
    }
})();

export default Materializer;
