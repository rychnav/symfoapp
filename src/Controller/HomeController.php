<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController
 * @package App\Controller
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
    public function home(): Response
    {
        return $this->render('home/home.html.twig');
    }
}
