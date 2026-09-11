<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageErrorController extends AbstractController
{
    #[Route('/page-error', name: 'page_error')]
    public function PageError(): Response
    {
        return $this->render('page_error/pageError.html.twig', [
            
        ]);
    }
}
