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

    /** Reinitialization for Ajax pages */

    let initTooltips = () => {
        let tooltips = document.querySelectorAll('.tooltipped');
        M.Tooltip.init(tooltips, {});
    };

    return {
        autoInit: autoInit,
        initSelects: initSelects,
        initTooltips: initTooltips,
    }
})();

export default Materializer;
