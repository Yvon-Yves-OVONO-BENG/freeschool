<?php

namespace App\Controller\Home;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeController extends AbstractController
{
    /**
     * @Route("/home-welcome", name="home_welcome")
     */
    public function welcome(): Response
    {
        return $this->render('home/welcome.html.twig', [
            'basicAdministration' => 'ok'
        ]);
    }

}
