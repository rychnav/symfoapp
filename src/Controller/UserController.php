<?php

namespace App\Controller;

use App\DTO\UserProfileData;
use App\Event\NoConfirmedAccount;
use App\Event\ProfileUpdateSuccess;
use App\Form\ProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class AdminController
 * @package App\Controller
 * @Route(
 *     path="/{_locale}/user",
 *     defaults={"_locale"="%default_locale%"},
 *     requirements={"_locale": "%app_locales%"},
 * )
 */
class UserController extends AbstractController
{
    /**
     * @Route(
     *     path="/{id}",
     *     name="user_profile",
     *     methods={"GET", "POST"},
     * )
     */
    public function showProfile(
        EventDispatcherInterface $dispatcher,
        int $id,
        UserPasswordEncoderInterface $encoder,
        Request $request,
        Security $security
    ): Response {
        $currentUser = $security->getUser();

        if ($currentUser->getId() !== $id) {
            return $this->redirectToRoute('user_profile', ['id' => $currentUser->getId()]);
        }

        $em = $this->getDoctrine()->getManager();
        $dto = new UserProfileData;

        $form = $this->createForm(ProfileType::class, $dto, [
            'action' => $request->getUri(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pass the old user into event to get changes after updating.
            $event = new ProfileUpdateSuccess($currentUser);
            $data = $form->getData();

            $isNewEmail = $data->email !== $currentUser->getEmail();
            $isNewFirstName = $data->firstName !== $currentUser->getFirstName();

            if ($isNewFirstName || $isNewEmail) {
                if ($isNewFirstName) {
                    $currentUser->setFirstName($data->firstName);
                }

                if ($isNewEmail) {
                    $currentUser->setConfirmedAt(null);
                    $currentUser->setEmail($data->email);

                    $noConfirmed = new NoConfirmedAccount();
                    $dispatcher->dispatch($noConfirmed, $noConfirmed::NAME);
                }

                $em->persist($currentUser);
                $em->flush();

                $dispatcher->dispatch($event, ProfileUpdateSuccess::NAME);
            }
        }

        return $this->render('profile/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
