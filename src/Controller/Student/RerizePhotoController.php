<?php

namespace App\Controller\Student;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class RerizePhotoController extends AbstractController
{
    #[Route("/resize", name:"student_resize")]
    public function rerizePhoto(): Response
    {
        return new Response('Resize');
    }
}