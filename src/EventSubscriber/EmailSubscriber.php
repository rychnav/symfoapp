<?php

namespace App\EventSubscriber;

use App\DTO\FlashAction;
use App\DTO\FlashMessage;
use App\Event\EmailFail;
use App\Event\EmailSuccess;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    private $logger;
    private $requestStack;
    private $session;
    private $urlGenerator;

    public function __construct(
        LoggerInterface $logger,
        RequestStack $requestStack,
        SessionInterface $session,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailFail::NAME => 'onEmailFail',
            EmailSuccess::NAME => 'onEmailSuccess',
        ];
    }

    public function onEmailSuccess(EmailSuccess $event): void
    {
        $route = $this->requestStack->getMasterRequest()->get('_route');

        $flashMessage = new FlashMessage(
            'success',
            $event->getFlashText()
        );

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info('The email was sent', [
            'route' => $route,
        ]);
    }

    public function onEmailFail(EmailFail $event): void
    {
        $flashAction = new FlashAction(
            $event->getActionText(),
            $this->urlGenerator->generate($event->getActionRoute())
        );

        $flashMessage = new FlashMessage(
            'error',
            $event->getFlashText(),
            $flashAction
        );

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info('The email was not sent', [
            'route' => $this->requestStack->getMasterRequest()->get('_route'),
            'exception' => $event->getThrowable()->getMessage(),
        ]);
    }
}
