<?php

namespace App\Controller;

use App\Service\TargetPathResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController
 * @package App\Controller
 * @Route(
 *     path="/{_locale}",
 *     defaults={"_locale"="%default_locale%"},
 *     requirements={"_locale": "%app_locales%"},
 * )
 */
class HomeController extends AbstractController
{
    /**
     * @Route(
     *     path="",
     *     name="home",
     *     methods={"GET"},
     * )
     * @return Response
     */
    public function home(TargetPathResolver $targetPathResolver): Response
    {
        $targetPathResolver->setPath();

        return $this->render('home/home.html.twig');
    }
}
