<?php

namespace App\Controller;

use App\Email\Feedback;
use App\Event\EmailFail;
use App\Event\EmailSuccess;
use App\Form\ContactsType;
use App\Service\TargetPathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ContactsController.
 *
 * @Route(
 *     path="/{_locale}/contacts",
 *     defaults={"_locale"="%default_locale%"},
 *     requirements={"_locale": "%app_locales%"},
 * )
 */
class ContactsController extends AbstractController
{
    /**
     * @Route(
     *     path="",
     *     name="contacts",
     *     methods={"GET|POST"},
     * )
     */
    public function send(
        EventDispatcherInterface $dispatcher,
        MailerInterface $mailer,
        Request $request,
        string $adminEmail,
        TargetPathResolver $targetPathResolver
    ): Response {
        $feedback = new Feedback();

        $form = $this->createForm(ContactsType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $mailer->send($feedback->fillEmail($adminEmail));
            } catch(TransportExceptionInterface $exception) {
                $feedbackFail = new EmailFail($exception, 'contacts');
                $dispatcher->dispatch($feedbackFail, $feedbackFail::NAME);

                return $this->render('contacts/contacts.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $feedbackSuccess = new EmailSuccess();
            $dispatcher->dispatch($feedbackSuccess, $feedbackSuccess::NAME);

            return $this->redirect($targetPathResolver->getPath());
        }

        return $this->render('contacts/contacts.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
