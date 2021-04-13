<?php

namespace App\Controller;

use App\DTO\UserEntityData;
use App\Email\RegisterConfirmation;
use App\Email\ResetConfirmation;
use App\Entity\ConfirmToken;
use App\Entity\User;
use App\Event\ConfirmationFail;
use App\Event\EmailFail;
use App\Event\EmailSuccess;
use App\Event\NoConfirmedAccount;
use App\Event\RegisterSuccess;
use App\Event\ShouldWait;
use App\Form\ConfirmEmailType;
use App\Form\LoginType;
use App\Form\RegisterType;
use App\Form\ResetPasswordType;
use App\Security\LoginFormAuthenticator;
use App\Service\TargetPathResolver;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Security Controller.
 *
 * @Route(
 *     path="/{_locale}",
 *     defaults={"_locale"="%default_locale%"},
 *     requirements={"_locale": "%app_locales%"},
 * )
 */
class SecurityController extends AbstractController
{
    public const REGISTER_WITH_EMAIL = 'email';
    private const REGISTER_LIFETIME_MODIFIER = '+24 hours';
    private const RESET_LIFETIME_MODIFIER = '+2hours';

    /**
     * @Route(
     *     path="/login",
     *     name="login",
     *     methods={"GET|POST"},
     * )
     */
    public function login(): Response
    {
        return $this->render('security/login.html.twig', [
            'form' => $this->createForm(LoginType::class)->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/logout",
     *     name="logout",
     *     methods={"GET"},
     * )
     */
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route(
     *  path="/register",
     *  name="register",
     *  methods={"GET|POST"},
     * )
     */
    public function register(
        EventDispatcherInterface $dispatcher,
        GuardAuthenticatorHandler $guardHandler,
        LoginFormAuthenticator $loginFormAuthenticator,
        MailerInterface $mailer,
        Request $request,
        string $noReplyAddress,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $dto = new UserEntityData();

        $form = $this->createForm(RegisterType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $dto->toEntity($form->getData(), new User(), $encoder);
            $user->setAuthType(self::REGISTER_WITH_EMAIL);
            $user->setRegisterAt(new DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $onSuccess = new RegisterSuccess($user);
            $dispatcher->dispatch($onSuccess, $onSuccess::NAME);

            $token = new ConfirmToken();
            $token->setPublicToken(bin2hex(random_bytes(32)));
            $token->setExpiresAt((new DateTime())->modify(self::REGISTER_LIFETIME_MODIFIER));
            $token->setSecret($em->getUnitOfWork()->getSingleIdentifierValue($user));

            $user->setToken($token);

            try {
                $mailer->send((new RegisterConfirmation($translator))->fillEmail($user, $noReplyAddress));
            } catch(TransportExceptionInterface $exception) {
                $em->remove($user);
                $em->flush();

                $emailFail = new EmailFail($exception, 'register', 'The confirmation email was not sent');
                $dispatcher->dispatch($emailFail, $emailFail::NAME);

                return $this->render('/security/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $em->persist($token);
            $em->flush();

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user, $request, $loginFormAuthenticator, 'main'
            );
        }

        return $this->render('/security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *  path="/register/confirmation/{id<\d+>}/{token}",
     *  name="register_confirmation",
     *  methods={"GET|POST"},
     * )
     */
    public function confirmRegister(): Response
    {
        return $this->render('security/login.html.twig', [
            'form' => $this->createForm(LoginType::class)->createView(),
        ]);
    }

    /**
     * @Route(
     *  path="/register/confirm",
     *  name="register_confirm",
     *  methods={"GET|POST"},
     * )
     */
    public function sendRegisterMail(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        MailerInterface $mailer,
        Request $request,
        Security $security,
        string $noReplyAddress,
        TargetPathResolver $targetPathResolver,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $actionUrl = $urlGenerator->generate('register_confirm');

        // 1. Create form
        $form = $this->createForm(ConfirmEmailType::class, null, [
            'action' => $actionUrl,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 2. Is email correct?
            $email = $form->get('email')->getData();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $confirmationFail = new ConfirmationFail($email, ConfirmationFail::EMAIL_NOT_FOUND_MESSAGE);
                $dispatcher->dispatch($confirmationFail, $confirmationFail::NAME);

                return $this->render('security/confirm-email.html.twig', [
                    'form' => $form->createView()
                ]);
            }

            // 3. Avoid many tokens and many emails for one user.
            $token = $user->getToken();
            if ($token) {
                $expireDate = $token->getExpiresAt();
                $now = new DateTime();

                if($expireDate < $now) {
                    $entityManager->remove($token);
                    $entityManager->flush();
                } else {
                    $shouldWait = new ShouldWait($user, $now, $expireDate);
                    $dispatcher->dispatch($shouldWait, $shouldWait::NAME);

                    return $this->redirect($targetPathResolver->getPath());
                }
            }

            // 4. If the account already verified
            if ($user->getConfirmedAt()) {
                $currentUser = $security->getUser();
                $message = $currentUser->getEmail() === $email ? 'Email is already verified' : 'This email is not yours';

                $alreadyConfirmed = new ConfirmationFail($email, $message);
                $dispatcher->dispatch($alreadyConfirmed, $alreadyConfirmed::NAME);

                if ($this->getUser()) {
                    return $this->redirect($targetPathResolver->getPath());
                } else {
                    return $this->redirectToRoute('login');
                }
            }

            // 5. Create a new token.
            $token = new ConfirmToken();
            $token->setPublicToken(bin2hex(random_bytes(32)));
            $token->setExpiresAt((new DateTime())->modify(self::REGISTER_LIFETIME_MODIFIER));
            $token->setSecret($entityManager->getUnitOfWork()->getSingleIdentifierValue($user));

            $user->setToken($token);

            // 4. Send email
            try {
                $mailer->send((new RegisterConfirmation($translator))->fillEmail($user, $noReplyAddress));
            } catch(TransportExceptionInterface $exception) {
                $entityManager->remove($token);

                $emailFail = new EmailFail($exception, 'register_confirm', 'The confirmation email was not sent');
                $dispatcher->dispatch($emailFail, $emailFail::NAME);

                return $this->render('/security/confirm-email.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->flush();

            $emailSuccess = new EmailSuccess('The confirmation email was successfully sent');
            $dispatcher->dispatch($emailSuccess, $emailSuccess::NAME);

            return $this->redirect($targetPathResolver->getPath());
        }

        return $this->render('security/confirm-email.html.twig', [
            'form' => $this->createForm(ConfirmEmailType::class, null, [
                'action' => $actionUrl,
            ])->createView(),
        ]);
    }

    /**
     * @Route(
     *  path="/reset/confirm",
     *  name="reset_confirm",
     *  methods={"GET|POST"},
     * )
     */
    public function sendResetMail(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        MailerInterface $mailer,
        Request $request,
        TargetPathResolver $targetPathResolver,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        string $noReplyAddress
    ): Response {
        // 1. Create form
        $form = $this->createForm(ConfirmEmailType::class, null, [
            'action' => $urlGenerator->generate('reset_confirm'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 2. Is email correct?
            $email = $form->get('email')->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $confirmationFail = new ConfirmationFail($email, ConfirmationFail::EMAIL_NOT_FOUND_MESSAGE);
                $dispatcher->dispatch($confirmationFail, $confirmationFail::NAME);

                return $this->render('security/confirm-email.html.twig', [
                    'page_title' => 'Resetting password',
                    'form' => $form->createView()
                ]);
            }

            // 3. Check the account
            if (!$user->getConfirmedAt()) {
                $noConfirmed = new NoConfirmedAccount('First you need to verify your account');
                $dispatcher->dispatch($noConfirmed, $noConfirmed::NAME);

                return $this->render('security/confirm-email.html.twig', [
                    'form' => $this->createForm(ConfirmEmailType::class, null, [
                        'action' => $urlGenerator->generate('register_confirm'),
                        'email' => $email,
                    ])->createView(),
                ]);
            }

            // 4. Is not Token expired?
            $oldToken = $user->getToken();
            $now = new DateTime();

            if ($oldToken && $oldToken->getExpiresAt() < $now) {
                // Delete token if expired date in the past.
                $em->remove($oldToken);
                $em->flush();

                $expiredToken = new ConfirmationFail($email, ConfirmationFail::EXPIRED_RESET_TOKEN_MESSAGE);
                $dispatcher->dispatch($expiredToken, $expiredToken::NAME);

                return $this->render('security/confirm-email.html.twig', [
                    'page_title' => 'Resetting password',
                    'form' => $form->createView()
                ]);
            }

            $lastResetDate = $user->getLastResetAt();

            if ($lastResetDate) {
                $resetLifetime = $lastResetDate->modify(self::RESET_LIFETIME_MODIFIER);

                // 4. User can reset password only once every two hours
                if ($resetLifetime > $now) {
                    $shouldWait = new ShouldWait($user, $now, $resetLifetime);
                    $dispatcher->dispatch($shouldWait, $shouldWait::NAME);

                    return $this->redirect($targetPathResolver->getPath());
                }
            }

            // 5. Set new Token
            $resetToken = new ConfirmToken();
            $resetToken->setPublicToken(bin2hex(random_bytes(32)));
            $resetToken->setExpiresAt($now->modify(self::RESET_LIFETIME_MODIFIER));
            $resetToken->setSecret($em->getUnitOfWork()->getSingleIdentifierValue($user));

            $user->setToken($resetToken);

            try {
                $mailer->send((new ResetConfirmation($translator))->fillEmail($user, $noReplyAddress));
            } catch(TransportExceptionInterface $exception) {
                $emailFail = new EmailFail($exception, 'reset_confirm', 'The confirmation email was not sent');
                $dispatcher->dispatch($emailFail, $emailFail::NAME);

                $em->remove($resetToken);

                return $this->render('security/confirm-email.html.twig', [
                    'page_title' => 'Resetting password',
                    'form' => $form->createView()
                ]);
            }

            $emailSuccess = new EmailSuccess('The confirmation email was successfully sent');
            $dispatcher->dispatch($emailSuccess, $emailSuccess::NAME);

            $em->flush();

            return $this->redirect($targetPathResolver->getPath());
        }

        return $this->render('security/confirm-email.html.twig', [
            'page_title' => 'Resetting password',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(
     *  path="/reset/{id<\d+>}/{token}",
     *  name="reset_password",
     *  methods={"GET|POST"},
     * )
     */
    public function resetPassword(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        GuardAuthenticatorHandler $guardHandler,
        LoginFormAuthenticator $loginFormAuthenticator,
        int $id,
        Request $request,
        string $token,
        TargetPathResolver $targetPathResolver,
        UrlGeneratorInterface $urlGenerator,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $resetToken = $em->getRepository(ConfirmToken::class)->findOneBy(['publicToken' => $token]);
        $user = $em->getRepository(User::class)->find($id);
        $now = new DateTime();

        // 1. Check user exists

        if (!$user) {
            $idNotFound = new ConfirmationFail(null, ConfirmationFail::ID_NOT_FOUND_MESSAGE);
            $dispatcher->dispatch($idNotFound, $idNotFound::NAME);

            return $this->redirectToRoute('home');
        }

        // 2. Check token exists

        $email = $user->getEmail();

        if (!$resetToken || $resetToken !== $user->getToken()) {
            $invalidLink = new ConfirmationFail($email, ConfirmationFail::INVALID_RESET_TOKEN_MESSAGE);
            $dispatcher->dispatch($invalidLink, $invalidLink::NAME);

            return $this->redirectToRoute('home');
        }

        // 3. Check last reset lifetime

        $lastResetDate = $user->getLastResetAt();
        $resetLifetime = null;

        if ($lastResetDate) {
            $resetLifetime = $user->getLastResetAt()->modify(self::RESET_LIFETIME_MODIFIER);
        }

        if ($resetLifetime > $now) {
            $shouldWait = new ShouldWait($user, $now, $resetLifetime);
            $dispatcher->dispatch($shouldWait, $shouldWait::NAME);

            return $this->redirect($targetPathResolver->getPath());
        }

        // 4. Is token expired?

        $expiredDate = $resetToken->getExpiresAt();

        if ($expiredDate < $now) {
            $expiredToken = new ConfirmationFail($email, ConfirmationFail::EXPIRED_RESET_TOKEN_MESSAGE);
            $dispatcher->dispatch($expiredToken, $expiredToken::NAME);

            $em->remove($resetToken);
            $em->flush();

            // 1. Create form
            $form = $this->createForm(ConfirmEmailType::class, null, [
                'action' => $urlGenerator->generate('reset_confirm'),
            ]);

            return $this->render('security/confirm-email.html.twig', [
                'page_title' => 'Resetting password',
                'form' => $form->createView()
            ]);
        }

        // 5. Is token valid?

        $identifier = $em->getUnitOfWork()->getSingleIdentifierValue($user);
        $secret = $resetToken->encode($token, $identifier, $resetToken->getExpiresAt());

        if (!hash_equals($secret, $resetToken->getToken())) {
            $invalidToken = new ConfirmationFail($email, 'Something went wrong');
            $dispatcher->dispatch($invalidToken, $invalidToken::NAME);

            $em->remove($resetToken);
            $em->flush();

            return $this->redirectToRoute('reset_confirm');
        }

        // 6. OK. Reset password

        $form = $this->createForm(ResetPasswordType::class, null, [
            'token' => $token,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $encoder->encodePassword($user, $form->get('password')->getData());
            $user->setPassword($password);
            $user->setLastResetAt($now);

            $token = $user->getToken();

            $em->persist($token);
            $em->remove($user->getToken());
            $em->flush();

            // If bad user have access to mail, so he knows the right email in any case.
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user, $request, $loginFormAuthenticator, 'main'
            );
        }

        return $this->render('security/reset-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
