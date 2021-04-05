<?php

namespace App\Security;

use App\Entity\User;
use App\Event\LoginFail;
use App\Event\LoginSuccess;
use App\Form\LoginType;
use App\Service\TargetPathResolver;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    private const CSRF_TOKEN_ID = '_token';
    private const FORM_NAME = 'login';
    public const LOGIN_ROUTE = 'login';

    private $csrfTokenManager;
    private $entityManager;
    private $eventDispatcher;
    private $formFactory;
    private $passwordEncoder;
    private $targetPathResolver;
    private $twig;
    private $urlGenerator;

    public function __construct(
        AuthenticationUtils $authenticationUtils,
        CsrfTokenManagerInterface $csrfTokenManager,
        EntityManagerInterface $entityManager,
        Environment $environment,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory,
        TargetPathResolver $targetPathResolver,
        UrlGeneratorInterface $urlGenerator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->passwordEncoder = $passwordEncoder;
        $this->targetPathResolver = $targetPathResolver;
        $this->twig = $environment;
        $this->urlGenerator = $urlGenerator;
        $this->utils = $authenticationUtils;
    }

    /**
     * Does the authenticator support the given Request?
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * Get the authentication credentials from the request
     *  and return them as any type (e.g. an associate array).
     *
     * Whatever value you return here will be passed to `getUser()` and `checkCredentials()`.
     * For example, for a form login, you might:
     *      return [
     *          'username' => $request->request->get('_username'),
     *          'password' => $request->request->get('_password'),
     *      ];
     * Or for an API token that's on a header, you might use:
     *      return ['api_key' => $request->headers->get('X-API-TOKEN')];.
     *
     * @param Request $request
     *
     * @return mixed Any non-null value
     */
    public function getCredentials(Request $request): array
    {
        $credentials = [
            'email' => $request->request->all(self::FORM_NAME)['email'],
            'password' => $request->request->all(self::FORM_NAME)['password'],
            'csrf_token' => $request->request->all(self::FORM_NAME)[self::CSRF_TOKEN_ID],
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['email']
        );

        return $credentials;
    }

    /**
     * Return a UserInterface object based on the credentials.
     * The 'credentials' are the return value from `getCredentials()`.
     *
     * You may throw an 'AuthenticationException' if you wish.
     * If you return null, then a 'UsernameNotFoundException' is thrown for you.
     *
     * @param $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $credentials['email']]);

        if (!$user) {
            // Fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }

        return $user;
    }

    /**
     * Returns true if the credentials are valid.
     * If false is returned, authentication will fail.
     *
     * You may also throw an AuthenticationException
     *  if you wish to cause authentication to fail.
     *
     * The 'credentials' are the return value from getCredentials().
     *
     * @param $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $isValid = $this->passwordEncoder->isPasswordValid($user, $credentials['password']);

        if(!$isValid) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return $isValid;
    }

    /**
     * Returns the clear-text password contained in credentials if any.
     *
     * @param mixed $credentials - The user credentials
     *
     * @return string|null
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    /**
     * Called when authentication executed and was successful!
     * This should return the Response sent back to the user,
     *  like aRedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue,
     *  and the user will be authenticated.
     * This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param $providerKey
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        $user = $token->getUser();

        $event = new LoginSuccess($user);
        $this->eventDispatcher->dispatch($event, LoginSuccess::NAME);

        return new RedirectResponse($this->targetPathResolver->getPath());
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $form = $this->formFactory->create(LoginType::class);
        $form->handleRequest($request);

        if($exception && $form->isValid()) {
            $event = new LoginFail($exception);
            $this->eventDispatcher->dispatch($event, LoginFail::NAME);
        }

        $content = $this->twig->render('security/login.html.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }

    /**
     * Return the URL to the login page.
     *
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
