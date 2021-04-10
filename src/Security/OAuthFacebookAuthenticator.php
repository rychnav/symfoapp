<?php

namespace App\Security;

use App\Entity\User;
use App\Event\LoginSuccess;
use App\Repository\UserRepository;
use App\Service\TargetPathResolver;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class OAuthFacebookAuthenticator extends SocialAuthenticator
{
    public const REGISTER_WITH_FACEBOOK = 'facebook';

    private $clientRegistry;
    private $entityManager;
    private $eventDispatcher;
    private $targetPathResolver;
    private $urlGenerator;
    private $userRepository;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        TargetPathResolver $targetPathResolver,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->targetPathResolver = $targetPathResolver;
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
    }

    public function start(
        Request $request,
        AuthenticationException $authException = null
    ): RedirectResponse {
        return new RedirectResponse(
            '/connect/facebook',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function supports(Request $request): bool
    {
        return 'facebook_auth' === $request->attributes->get('_route');
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getFacebookClient());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $fbUser = $this->getFacebookClient()->fetchUserFromToken($credentials);
        $email = $fbUser->getEmail();

        $existingUser = $this->userRepository->findOneBy(['facebookId' => $fbUser->getId()])
            ?? $this->userRepository->findOneBy(['email' => $email]);

        $authUser = $existingUser ?? new User();

        $authUser->setEmail($email);
        $authUser->setFacebookId($fbUser->getId());
        $authUser->setAuthType(self::REGISTER_WITH_FACEBOOK);

        if(!$existingUser) {
            $authUser->setFirstName($fbUser->getName());
            $authUser->setRoles(['ROLE_USER']);

            $this->entityManager->persist($authUser);
        }

        $this->entityManager->flush();

        return $authUser;
    }

    public function getFacebookClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('facebook');
    }

    public function supportsRememberMe(): bool
    {
        return true;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ) {
        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        $providerKey
    ): ?Response {
        $event = new LoginSuccess($token->getUser());
        $this->eventDispatcher->dispatch($event, LoginSuccess::NAME);

        return new RedirectResponse($this->targetPathResolver->getPath());
    }
}
