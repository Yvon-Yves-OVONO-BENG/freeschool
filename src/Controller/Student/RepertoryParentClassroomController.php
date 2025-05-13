<?php

namespace App\Controller\Student;

use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Service\RepertoryClassroomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/student')]
class RepertoryParentClassroomController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classroomRepository, 
        protected RepertoryClassroomService $repertoryClassroomService, 
        )
    {}

    #[Route('/repertory-parent-classroom/{slug}', name: 'repertory_parent_classroom')]
    public function repertoryClassroom(Request $request, string $slug): Response
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

        $classroom = $this->classroomRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $pdf = $this->repertoryClassroomService->printRepertoryParentClassroom($school, $schoolYear, $classroom);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Repertory parent classroom of ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Répertoire des parents de la classe de ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }

    }
}
