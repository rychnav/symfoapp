<?php

namespace App\Controller;

use App\DTO\UserEntityData;
use App\Entity\User;
use App\Event\RegisterSuccess;
use App\Form\LoginType;
use App\Form\RegisterType;
use App\Security\LoginFormAuthenticator;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

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
        Request $request,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $dto = new UserEntityData();

        $form = $this->createForm(RegisterType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $dto->toEntity($form->getData(), new User(), $encoder);
            $user->setAuthType(self::REGISTER_WITH_EMAIL);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $onSuccess = new RegisterSuccess($user);
            $dispatcher->dispatch($onSuccess, $onSuccess::NAME);

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user, $request, $loginFormAuthenticator, 'main'
            );
        }

        return $this->render('/security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
