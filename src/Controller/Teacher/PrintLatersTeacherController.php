<?php

namespace App\Controller\Teacher;

use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Service\TeacherService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/teacher")]
class PrintLatersTeacherController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository,
        protected TeacherService $teacherService, 
        protected SchoolRepository $schoolRepository, 
        protected SequenceRepository $sequenceRepository, 
        )
    {}

    #[Route("/printLaters/{idS<[0-9]+>}/{idL<[0-9]+>}/{idC<[0-9]+>}", name:"teacher_printLaters")]
    public function printLatersTeacher(Request $request, int $idS, int $idL = 0, int $idC = 0): Response
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
        
        $schoolYear = $mySession->get('schoolYear');

        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $selectedSequence = $this->sequenceRepository->find($idS);

        $evaluations = $this->teacherService->getUnrecordedMark($idS, $idL);

        $pdf = $this->teacherService->printLaters($evaluations, $school, $schoolYear, $selectedSequence);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Laters of recording evaluation of term ".$selectedSequence->getSequence()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Retardataires dans les saisies des notes du trimestre ".$selectedSequence->getSequence()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    }
}