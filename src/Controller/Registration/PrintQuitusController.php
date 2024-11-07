<?php

namespace App\Controller\Registration;

use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\QuitusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

class PrintQuitusController extends AbstractController
{
    public function __construct(
        protected QuitusService $quitusService, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository,
        )
    {}

    #[Route('/print-quitus/{slugStudent}', name: 'print_quitus')]
    public function printQuitus(Request $request, string $slugStudent): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
            $schoolYear = $mySession->get('schoolYear');
        }
        else
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $schoolYear = $mySession->get('schoolYear');

        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $student = $this->studentRepository->findOneBySlug(['slug' => $slugStudent]);

        $pdf = $this->quitusService->printStudentQuitus($school, $schoolYear, $student);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Quittus of ".$student->getFullName()), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Quittus de ".$student->getFullName()), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }
}
