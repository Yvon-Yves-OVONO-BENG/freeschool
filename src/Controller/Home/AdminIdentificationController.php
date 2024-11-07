<?php

namespace App\Controller\Home;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminIdentificationController extends AbstractController
{
    /**
     * @Route("/home-adminIdentification", name="home_adminIdentification")
     */
    public function adminIdentification(): Response
    {
        return $this->render('home/adminIdentification.html.twig', [
            'home' => true,
        ]);
    }

}
