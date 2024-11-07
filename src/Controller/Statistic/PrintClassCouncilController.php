<?php

namespace App\Controller\Statistic;

use App\Repository\ClassroomRepository;
use App\Repository\TermRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class PrintClassCouncilController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository,
        protected ClassroomRepository $classroomRepository,
    )
    {}

    #[Route("/printClassCouncil", name:"statistic_printClassCouncil")]
    public function printClassCouncil(Request $request): Response
    {
        #les variables du formulaire
        $idC = $request->request->get('classroom');
        $idT = $request->request->get('term');
        
        #je récupère les slugs
        $slug = $this->classroomRepository->find($idC)->getSlug();
        $slugTerm = $this->termRepository->find($idT)->getSlug();

        return $this->redirectToRoute('report_printReport', [
            'slug' => $slug,
            'slugTerm' => $slugTerm,
            'council' => 1
        ]);
    }

}
