<?php

namespace App\Twig;

use Locale;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    private $requestStack;
    private $urlGenerator;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('locale_url', [$this, 'getLocaleUrl']),
            new TwigFunction('preferred_locale_url', [$this, 'getPreferredLocaleUrl']),
        ];
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
}
