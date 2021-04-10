<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController extends AbstractController
{
    /**
     * @Route(
     *     path="/connect/google",
     *     name="google_connect"
     * )
     */
    public function redirectToGoogleConnect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile',
            ], []);
    }

    /**
     * @Route(
     *     path="/google/auth",
     *     name="google_auth"
     * )
     */
    public function connectGoogleCheck() {}
}
