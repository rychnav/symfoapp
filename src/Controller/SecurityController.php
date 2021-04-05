<?php

namespace App\Controller;

use App\Form\LoginType;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
}
