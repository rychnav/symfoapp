<?php

namespace App\Controller;

use App\DTO\UserEntityData;
use App\Entity\User;
use App\Event\UserCreateSuccess;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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

    /**
     * @Route(
     *     path="/create",
     *     name="user_create",
     *     methods={"GET|POST"},
     * )
     */
    public function createUser(
        EventDispatcherInterface $dispatcher,
        Request $request,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $dto = new UserEntityData();

        $form = $this->createForm(UserType::class, $dto, [
            'action' => $request->getUri(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $dto->toEntity($form->getData(), new User(), $encoder);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $event = new UserCreateSuccess($user);
            $dispatcher->dispatch($event, UserCreateSuccess::NAME);

            return $this->redirectToRoute('user_list');
        }

        return $this->render('/user/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
