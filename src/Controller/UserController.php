<?php

namespace App\Controller;

use App\DTO\UserEntityData;
use App\Entity\User;
use App\Event\UserCreateSuccess;
use App\Event\UserDeleteSuccess;
use App\Event\UserUpdateSuccess;
use App\Form\DeleteEntityType;
use App\Form\UserType;
use App\Service\Pager;
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
    private const ITEMS_PER_PAGE = 5;

    /**
     * @Route(
     *     path="/list/{page<\d+>?1}",
     *     name="user_list",
     *     methods={"GET"},
     * )
     */
    public function showAll(
        Pager $pager,
        Request $request
    ): Response {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $query = $repository->createQueryBuilder('u')->getQuery();
        $users = $pager->paginate($query, $request, self::ITEMS_PER_PAGE);

        if(!$users) {
            return $this->render('user/user-not-found.html.twig', [
                'icon' => '🧐',
                'message' => "Nobody's here",
            ]);
        }

        $lastPage = $pager->lastPage($users);

        if ($lastPage < $request->attributes->get('page')) {
            $request->attributes->set('page', $lastPage);

            return $this->redirectToRoute('user_list', [
                'page' => $lastPage,
            ]);
        }

        return $this->render('user/list.html.twig', [
            'lastPage' => $lastPage,
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

    /**
     * @Route(
     *     path="/{id}/update",
     *     name="user_update",
     *     methods={"GET|POST"},
     * )
     */
    public function updateUser(
        EventDispatcherInterface $dispatcher,
        int $id,
        Request $request,
        UserPasswordEncoderInterface $encoder
    ): Response {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);
        $dto = (new UserEntityData)->fromEntity($user);

        $form = $this->createForm(UserType::class, $dto, [
            'action' => $request->getUri(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pass the old user into event to get changes after updating.
            $event = new UserUpdateSuccess($user);
            $user = $dto->toEntity($form->getData(), $user, $encoder);

            $em->persist($user);
            $em->flush();
            $dispatcher->dispatch($event, UserUpdateSuccess::NAME);

            return $this->redirectToRoute('user_list');
        }

        return $this->render('/user/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/{id}/details",
     *     name="user_details",
     *     methods={"GET"},
     * )
     */
    public function showDetails(int $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);

        return $this->render('/user/details.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route(
     *     path="/{id}/confirm/delete",
     *     name="user_confirm_delete",
     *     methods={"GET|POST"},
     * )
     */
    public function delete(
        EventDispatcherInterface $dispatcher,
        int $id,
        Request $request
    ): Response {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);

        $form = $this->createForm(DeleteEntityType::class, null, [
            'action' => $request->getUri(),
            'entity' => 'user',
            'entity_title' => $user->getEmail(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($user);
            $em->flush();

            $event = new UserDeleteSuccess($user);
            $dispatcher->dispatch($event, UserDeleteSuccess::NAME);

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/delete.html.twig', [
            'form' => $form->createView(),
            'id' => $id
        ]);
    }
}
