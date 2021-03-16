<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller
 * @Route(
 *     path="/{_locale}/admin/user",
 *     defaults={"_locale"="%default_locale%"},
 *     requirements={"_locale": "%app_locales%"},
 * )
 */
class UserController extends AbstractController
{
    /**
     * @Route(
     *     path="/list",
     *     name="user_list",
     *     methods={"GET"},
     * )
     */
    public function showAll(): Response
    {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $users = $repository->findAll();

        if(!$users) {
            return $this->render('user/user-not-found.html.twig', [
                'icon' => '🧐',
                'message' => "Nobody's here",
            ]);
        }

        return $this->render('user/list.html.twig', [
            'users' => $users,
        ]);
    }
}
