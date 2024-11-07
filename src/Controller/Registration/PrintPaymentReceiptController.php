<?php

namespace App\Controller\Registration;

use App\Repository\SchoolRepository;
use App\Service\RegistrationService;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RegistrationHistoryRepository;
use App\Service\PaymentReceiptService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class PrintPaymentReceiptController extends AbstractController
{
    public function __construct( 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected PaymentReceiptService $paymentReceiptService, 
        protected RegistrationHistoryRepository $registrationHistoryRepository,
        )
    {}

    #[Route("/printPaymentReceipt/{slugStudent}", name:"registration_printPaymentReceipt")]
    public function printPaymentReceipt(Request $request, string $slugStudent): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $student = $this->studentRepository->findOneBySlug(['slug' => $slugStudent]);

        $registrationHistories = $this->registrationHistoryRepository->findBy([
            'student' => $student
            ], [
            'createdAt' => 'DESC'
            ]);

        $pdf = $this->paymentReceiptService->printPaymentReceiptService($registrationHistories, $school, $schoolYear, $student);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Payement receipt of ".$student->getFullName()), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Reçu de paiement de ".$student->getFullName()), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}
