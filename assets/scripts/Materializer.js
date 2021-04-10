import M from '@materializecss/materialize';
import Translations from './Translations.js';

let Materializer = (() => {

    let autoInit = () => {
        M.AutoInit();

        initDropdowns();
        initDatePickers();
    };

    /** Initiate modules separately to pass extra params */

    let initDropdowns = () => {
        let dropdowns = document.querySelectorAll('.dropdown-trigger');
        M.Dropdown.init(dropdowns, { constrainWidth: false });
    };

    let initDatePickers = (options) => {
        let datepickers = document.querySelectorAll('.datepicker');

        M.Datepicker.init(datepickers, { ...{
            'autoClose': true,
            'container': 'body',
            'firstDay': 1,
            'format': 'yyyy-mm-dd',
            'yearRange': [2010, (new Date).getFullYear() + 10],
            'showDaysInNextAndPreviousMonths': true,
            'showMonthAfterYear': true,
            'i18n': Translations.materialize_i18n(),
        }, ...options});
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
        initDatePickers: initDatePickers,
    }
})();

export default Materializer;
