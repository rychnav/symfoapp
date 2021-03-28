<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TargetPathResolver
{
    private const KEY = 'PREVIOUS_URL';
    private const DEFAULT_ROUTE = 'home';

    private $requestStack;
    private $session;
    private $urlGenerator;

    public function __construct(
        SessionInterface $session,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    // Set path for all the meaningful pages except modals yet...
    public function setPath(): void
    {
        $request = $this->requestStack->getMasterRequest();
        $this->session->set(self::KEY, $request->getUri());
    }

    public function hasPath(): bool
    {
        return $this->session->has(self::KEY);
    }

    public function getPath(): string
    {
        if ($this->hasPath()) {
            return $this->session->get(self::KEY);
        }

        return $this->urlGenerator->generate(self::DEFAULT_ROUTE);
    }
}
