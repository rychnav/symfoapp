<?php

namespace App\Twig;

use App\Service\IdBag;
use DateTime;
use IntlDateFormatter;
use Locale;
use ReflectionClass;
use Symfony\Bridge\Twig\Mime\WrappedTemplatedEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    private $entryPointLookup;
    private $requestStack;
    private $urlGenerator;
    private $publicDir;

    public static function getSubscribedServices(): array
    {
        return [
            EntrypointLookupInterface::class,
        ];
    }

    public function __construct(
        EntrypointLookupInterface $entryPointLookup,
        RequestStack $requestStack,
        string $publicDir,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entryPointLookup = $entryPointLookup;
        $this->publicDir = $publicDir;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('encore_entry_css_source', [$this, 'getEncoreEntryCssSource']),
            new TwigFunction('locale_url', [$this, 'getLocaleUrl']),
            new TwigFunction('mail_from_link', [$this, 'getMailFromLink']),
            new TwigFunction('preferred_locale_url', [$this, 'getPreferredLocaleUrl']),
            new TwigFunction('sort_params', [$this, 'getSortParams']),
            new TwigFunction('id_bag_session_key', [$this, 'getIdBagSessionKey']),
            new TwigFunction('trans_date', [$this, 'getTranslatedDate']),
        ];
    }

    public function getEncoreEntryCssSource(string $entryName): string
    {
        $files = $this->entryPointLookup->getCssFiles($entryName);

        $source = '';
        foreach ($files as $file) {
            $source .= file_get_contents($this->publicDir.'/'.$file);
        }

        return $source;
    }

    public function getLocaleUrl(string $locale): string
    {
        $attributes = $this->requestStack->getMasterRequest()->attributes;
        $route = $attributes->get('_route');
        $routeParams = $attributes->get('_route_params');

        $params = array_merge($routeParams, ['_locale' => $locale]);

        return $this->urlGenerator->generate($route, $params);
    }

    public function getPreferredLocaleUrl(): string
    {
        $request = $this->requestStack->getMasterRequest();
        $route = $request->attributes->get('_route');
        $routeParams = $request->attributes->get('_route_params');
        $routeParams['_locale'] = Locale::getPrimaryLanguage($request->getPreferredLanguage());

        return $this->urlGenerator->generate($route, $routeParams);
    }

    public function getSortParams(array $params): array
    {
        $ascIconId = 'arrow_drop_up';
        $descIconId = 'arrow_drop_down';

        $defaultOrder = 'asc';
        $defaultIcon = $ascIconId;

        $request = $this->requestStack->getMasterRequest();
        $requestOrder = $request->attributes->get('sort_order');
        $requestProperty = $request->attributes->get('sort_property');

        $oppositeIcon = $requestOrder === $defaultOrder ? $descIconId : $defaultIcon;
        $oppositeOrder = $requestOrder === $defaultOrder ? 'desc' : $defaultOrder;

        $isActiveProperty = $requestProperty === $params['sort_property'];
        $iconId = $isActiveProperty ? $oppositeIcon : $defaultIcon;

        return [
            'order' => $oppositeOrder,
            'icon_id' => $iconId,
            'is_active' => $isActiveProperty,
        ];
    }

    public function getIdBagSessionKey(string $entity): string
    {
        return (new ReflectionClass(IdBag::class))->getConstant(
            mb_strtoupper($entity) . '_BAG_KEY'
        );
    }

    public function getMailFromLink(WrappedTemplatedEmail $email): string
    {
        $subject = ucfirst($email->getSubject());
        $from = $email->getFrom()[0]->getAddress();

        return "mailto:$from?subject=$subject&amp;";
    }

    /** See available formats: http://userguide.icu-project.org/formatparse/datetime */
    public function getTranslatedDate(string $date, string $format): string
    {
        $request = $this->requestStack->getMasterRequest();
        $locale = Locale::getPrimaryLanguage($request->getLocale());

        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL);
        $formatter->setPattern($format);

        return mb_convert_case($formatter->format(new DateTime($date)), MB_CASE_TITLE, 'UTF-8');
    }
}
