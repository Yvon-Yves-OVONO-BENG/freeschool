<?php

namespace App\Controller\Home;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BasicAdministrationController extends AbstractController
{
    /**
     * @Route("/home-basicAdministation", name="home_basicAdministration")
     */
    public function basicAdministration(): Response
    {
        return $this->render('home/basicAdministration.html.twig', [
            'basicAdministration' => 'ok',
        ]);
    }

}