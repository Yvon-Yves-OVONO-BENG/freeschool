<?php

namespace App\Controller\Student;

use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\PrintFicheEleveService;
use App\Service\SchoolYearService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/student')]
class FicheEleveController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected StudentRepository $studentRepository, 
        protected PrintFicheEleveService $printFicheEleveService,
        )
    {}

    #[Route('/fiche-eleve/{slug}', name: 'fiche_eleve')]
    public function ficheEleve(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        #je récupère l'élève dont je veus imprimer lafcihe
        $student = $this->studentRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printFicheEleveService->print($student, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Sheet of ".$student->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Fiche de l'élève ".$student->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    
    }
}
