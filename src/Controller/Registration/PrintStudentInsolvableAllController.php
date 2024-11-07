<?php

namespace App\Controller\Registration;

use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\InsolvableAllService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class PrintStudentInsolvableAllController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classroomRepository, 
        protected InsolvableAllService $insolvableAllService, 
        )
    {}

    #[Route("/print-student-insolvable-all", name:"print_student_insolvable_all")]
    public function printStudentInsolvableAll(Request $request): Response
    {
        $mySession = $request->getSession();
        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }

        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $classrooms = $this->classroomRepository->findBy([ 'schoolYear' => $schoolYear]);

        $pdf = $this->insolvableAllService->printStudentInsolvableAll($classrooms, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("All Students insolvables"), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Tous les élèves insolvables"), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}