import Translator from 'bazinga-translator';

(async () => {

    const res = await fetch(`/translations/messages.json?locales=${Translator.locale}`);
    const json = await res.json();
    Translator.fromJSON(json);
})();

export default Translator;
