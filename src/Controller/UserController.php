<?php

namespace App\Controller;

use App\DTO\UserEntityData;
use App\DTO\UserSearchData;
use App\Entity\User;
use App\Event\CsrfTokenFail;
use App\Event\UserCreateSuccess;
use App\Event\UserDeleteSuccess;
use App\Event\UserUpdateSuccess;
use App\Form\DeleteEntityType;
use App\Form\UserSearchType;
use App\Form\UserType;
use App\Service\IdBag;
use App\Service\Pager;
use App\Service\TargetPathResolver;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    private const USER_BAG_KEY = IdBag::USER_BAG_KEY;

    /**
     * @Route(
     *     path="/list/{page<\d+>?1}",
     *     name="user_list",
     *     methods={"GET"},
     * )
     */
    public function showAll(
        IdBag $idBag,
        Pager $pager,
        Request $request,
        TargetPathResolver $targetPathResolver
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

        $targetPathResolver->setPath();
        $idBag->clear(self::USER_BAG_KEY);

        return $this->render('user/list.html.twig', [
            'lastPage' => $lastPage,
            'users' => $users,
        ]);
    }

    /**
     * @Route(
     *     path="/list/sort/{page<\d+>?1}/{sort_property<id|firstName|email|roles|authType|registerAt>?id}/{sort_order<asc|desc>?asc}",
     *     name="user_list_sort",
     *     methods={"GET"},
     * )
     */
    public function sort(
        Pager $pager,
        Request $request,
        string $sort_property,
        string $sort_order,
        TargetPathResolver $targetPathResolver
    ): Response {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $query = $repository->sort($sort_property, $sort_order);

        $results = $pager->paginate($query, $request, self::ITEMS_PER_PAGE);
        $lastPage = $pager->lastPage($results);

        $targetPathResolver->setPath();

        return $this->render('user/list.html.twig', [
            'users' => $results,
            'lastPage' => $lastPage,
            'sort_property' => $sort_property,
            'sort_order' => $sort_order,
        ]);
    }

    /**
     * @Route(
     *     path="/search",
     *     name="user_search_form",
     *     methods={"GET|POST"},
     * )
     */
    public function searchForm(Request $request): Response
    {
        $dto = new UserSearchData();

        $form = $this->createForm(UserSearchType::class, $dto, [
            'action' => $this->generateUrl('user_search_form'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $dto->fromForm($form);

            return $this->redirectToRoute('user_list_search', [
                'firstName' => $data->firstName,
                'email' => $data->email,
                'roles' => $data->roles,
                'authType' => $data->authType,
                'registerFrom' => $data->registerRange['from'],
                'registerTo' => $data->registerRange['to'],
            ]);
        }

        return $this->render('user/search.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/list/{page<\d+>?1}/search/{firstName}/{email}/{roles}/{authType}/{registerFrom}/{registerTo}",
     *     name="user_list_search",
     *     methods={"GET"},
     * )
     */
    public function search(
        IdBag $idBag,
        Pager $pager,
        Request $request,
        string $firstName,
        string $email,
        string $roles,
        string $authType,
        string $registerFrom,
        string $registerTo,
        TargetPathResolver $targetPathResolver
    ): Response {
        $targetPathResolver->setPath();

        $repository = $this->getDoctrine()->getRepository(User::class);
        $query = $repository->search($firstName, $email, $roles, $authType, $registerFrom, $registerTo);

        $idBag->saveFromQuery($query, self::USER_BAG_KEY);

        $users = $pager->paginate($query, $request, self::ITEMS_PER_PAGE);

        if(!$users->count()) {
            return $this->render('user/user-not-found.html.twig', [
                'icon' => '🧐',
                'message' => "Nobody's here",
                'users' => null,
            ]);
        }

        $lastPage = $pager->lastPage($users);

        return $this->render('user/list.html.twig', [
            'users' => $users,
            'lastPage' => $lastPage,
            'firstName' => $firstName,
            'email' => $email,
            'roles' => $roles,
            'authType' => $authType,
            'registerFrom' => $registerFrom,
            'registerTo' => $registerTo,
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
            $user->setRegisterAt(new DateTime());

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
        TargetPathResolver $targetPathResolver,
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

            if ($dto->email !== $user->getEmail()) {
                $user->setConfirmedAt(null);
            }

            $user = $dto->toEntity($form->getData(), $user, $encoder);

            $em->persist($user);
            $em->flush();
            $dispatcher->dispatch($event, UserUpdateSuccess::NAME);

            return $this->redirect($targetPathResolver->getPath());
        }

        return $this->render('/user/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/{id}/edit/{property}",
     *     name="user_edit",
     *     methods={"POST"},
     * )
     */
    public function editProperty(
        int $id,
        PropertyAccessorInterface $propertyAccessor,
        Request $request,
        string $property,
        TranslatorInterface $translator,
        ValidatorInterface $validator
    ): Response {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);
        $rawValue = $request->get($property);
        $value = $property === 'roles' ? [$rawValue] : $rawValue;

        $csrf = $request->request->get('csrf_token');
        if (!$this->isCsrfTokenValid('user_list', $csrf)) {
            $message = 'Invalid CSRF token.';

            return $this->json([
                'value' => $propertyAccessor->getValue($user, $property),
                'errors' => [$translator->trans($message, [], 'validators')],
            ]);
        }

        $constraints = $validator->getMetadataFor(UserEntityData::class)
            ->getPropertyMetadata($property)[0]
            ->constraints;

        $errors = $validator->validate(
            $value,
            $constraints,
            ['Default', 'update']
        );

        if (count($errors) > 0) {
            $messages = [];

            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $this->json([
                'value' => $propertyAccessor->getValue($user, $property),
                'errors' => $messages,
            ]);
        }

        if ($property === 'email' and $value !== $user->getEmail()) {
            $user->setConfirmedAt(null);
        }

        $propertyAccessor->setValue(
            $user,
            $property,
            $value
        );

        $em->flush();

        return $this->json([
            'value' => $value,
            'property' => $property,
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

    /**
     * @Route(
     *     path="/delete/multiply",
     *     name="user_delete_multiply",
     *     methods={"POST"},
     * )
     */
    public function deleteMultiply(
        EventDispatcherInterface $dispatcher,
        Request $request,
        TargetPathResolver $targetPathResolver
    ): Response {
        $ids = $request->get('ids');
        $em = $this->getDoctrine()->getManager();

        $csrf = $request->request->get('csrf_token');
        // TODO: Token ID should be the secret. See: https://symfony.com/doc/current/configuration/secrets.html
        if (!$this->isCsrfTokenValid('user_list', $csrf)) {
            $event = new CsrfTokenFail($request);
            $dispatcher->dispatch($event, CsrfTokenFail::NAME);
        } else {
            foreach(json_decode($ids) as $id) {
                $user = $em->getRepository(User::class)->find($id);
                $em->remove($user);

                $event = new UserDeleteSuccess($user);
                $dispatcher->dispatch($event, UserDeleteSuccess::NAME);
            }

            $em->flush();
        }

        return $this->redirect($targetPathResolver->getPath());
    }
}
