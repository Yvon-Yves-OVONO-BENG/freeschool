<?php

namespace App\Controller\RegisterAndList;

use App\Repository\SchoolRepository;
use App\Repository\SubSystemRepository;
use App\Service\RegisterAndListService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintAllStudentListController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected RegisterAndListService $registerAndListService, 
        )
    {}
    ///////////////////IMPRIME TOUS LES ELEVES DE L'ETABLISSEMENT
    #[Route("/printAllStudentList", name:"register_and_list_printAllStudentList")]
    public function printAllStudentList(Request $request): Response
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
        

        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $studentListCycle1 = $this->registerAndListService->getAllStudentListCycle1($schoolYear, $subSystem);

        $studentListCycle2 = $this->registerAndListService->getAllStudentListCycle2($schoolYear, $subSystem);

        $pdf = $this->registerAndListService->printAllStudentList($studentListCycle1, $studentListCycle2, $school, $schoolYear, $subSystem);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Students list"), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Liste des élèves"), "I"), 200, ['Content-Type' => 'application/pdf']);
        }
        
    }
}
