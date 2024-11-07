<?php

namespace App\Controller\Registration;

use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RegistrationHistoryRepository;
use App\Service\PrintStudentFeesHistoryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class PrintStudentFeesHistoryController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository,
        protected PrintStudentFeesHistoryService $printStudentFeesHistoryService, 
        )
    {}

    #[Route("/printStudentFeesHistory/{slugStudent}", name:"registration_printStudentFeesHistory")]
    public function printStudentFeesHistory(Request $request, string $slugStudent): Response
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
        // $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $student = $this->studentRepository->findOneBySlug(['slug' => $slugStudent]);

        $registrationHistories = $this->registrationHistoryRepository->findBy([
            'student' => $student
        ], [
            'createdAt' => 'DESC'
        ]);

        $pdf = $this->printStudentFeesHistoryService->printStudentRegistrationHistory($registrationHistories, $school, $schoolYear, $student);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Fees history of ".$student->getFullname()), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Historique de paiement de ".$student->getFullname()), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}
