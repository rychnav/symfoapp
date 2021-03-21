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

    /** Reinitialization for Ajax forms */

    let initSelects = () => {
        let selects = document.querySelectorAll('select');
        M.FormSelect.init(selects, {});
    };

    return {
        autoInit: autoInit,
        initSelects: initSelects,
    }
})();

export default Materializer;
