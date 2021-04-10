import Translator from './Translator.js';

let Translations = (() => {

    let getMonths = () => {
        return [
            Translator.trans('January'),
            Translator.trans('February'),
            Translator.trans('March'),
            Translator.trans('April'),
            Translator.trans('May'),
            Translator.trans('June'),
            Translator.trans('July'),
            Translator.trans('August'),
            Translator.trans('September'),
            Translator.trans('October'),
            Translator.trans('November'),
            Translator.trans('December')
    ]};

    let getMonthsShort = () => {
        return [
            Translator.trans('Jan'),
            Translator.trans('Feb'),
            Translator.trans('Mar'),
            Translator.trans('Apr'),
            Translator.trans('May_short'),
            Translator.trans('Jun'),
            Translator.trans('Jul'),
            Translator.trans('Aug'),
            Translator.trans('Sep'),
            Translator.trans('Oct'),
            Translator.trans('Nov'),
            Translator.trans('Dec')
    ]};

    let getWeekdays = () => {
        return [
            Translator.trans('Sunday'),
            Translator.trans('Monday'),
            Translator.trans('Tuesday'),
            Translator.trans('Wednesday'),
            Translator.trans('Thursday'),
            Translator.trans('Friday'),
            Translator.trans('Saturday')
        ]
    };

    let getWeekdaysShort = () => {
        return [
            Translator.trans('Sun'),
            Translator.trans('Mon'),
            Translator.trans('Tue'),
            Translator.trans('Wed'),
            Translator.trans('Thu'),
            Translator.trans('Fri'),
            Translator.trans('Sat')
        ];
    };

    let getWeekdaysAbbrev = () => {
        return [
            Translator.trans('S'),
            Translator.trans('M'),
            Translator.trans('T'),
            Translator.trans('W'),
            Translator.trans('T_abbr'),
            Translator.trans('F'),
            Translator.trans('S_abbr')
        ];
    };

    let materialize_i18n = () => {
        return {
            cancel: Translator.trans('Cancel'),
            clear: Translator.trans('Clear'),
            done: Translator.trans('Ok'),
            previousMonth: '‹',
            nextMonth: '›',
            months: getMonths(),
            monthsShort: getMonthsShort(),
            weekdays: getWeekdays(),
            weekdaysShort: getWeekdaysShort(),
            weekdaysAbbrev: getWeekdaysAbbrev()
        };
    };

    return {
        materialize_i18n: materialize_i18n,
    }
})();

export default Translations;
