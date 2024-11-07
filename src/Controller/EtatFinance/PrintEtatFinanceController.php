<?php

namespace App\Controller\EtatFinance;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use App\Service\PrintEtatFinanceService;
use App\Repository\EtatDepenseRepository;
use App\Repository\EtatFinanceRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/etat-finance")]
class PrintEtatFinanceController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected DepenseRepository $depenseRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        protected EtatFinanceRepository $etatFinanceRepository, 
        protected EtatDepenseRepository $etatDepenseRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected PrintEtatFinanceService $printEtatFinanceService, 
        )
    {}

    #[Route("/print-etat-finance", name:"print_etat_finance")]
    public function printEtatFinance(Request $request): Response
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
            $sessionSchoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
  
        $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $sessionSchoolYear->getSchoolYear() ]);

        //je récupère l'etat financier
        $etatFinance = $this->registrationRepository->getEtatFinancier ($schoolYear);

        //je récupère l'état des dépenses
        $etatDepenses = $this->depenseRepository->getSumSpendingPerRubrique($schoolYear);
        

        $apee = 0;
        $computer = 0;
        $cleanSchool = 0;
        $medicalBooklet = 0;
        $stamp = 0;
        $photo = 0;

        for ($i=0; $i < count($etatDepenses); $i++) 
        { 
            if ($etatDepenses[$i]['RUBRIQUE'] == ConstantsClass::APEE) 
            {
                $apee = $etatDepenses[$i]['SOMME'];

            }elseif ($etatDepenses[$i]['RUBRIQUE'] == ConstantsClass::COMPUTER)
            {
                $computer = $etatDepenses[$i]['SOMME'];

            }elseif ($etatDepenses[$i]['RUBRIQUE'] == ConstantsClass::CLEAN_SCHOOL)
            {
                $cleanSchool = $etatDepenses[$i]['SOMME'];

            }elseif ($etatDepenses[$i]['RUBRIQUE'] == ConstantsClass::MEDICAL_BOOKLET)
            {
                $medicalBooklet = $etatDepenses[$i]['SOMME'];

            }elseif($etatDepenses[$i]['RUBRIQUE'] == ConstantsClass::STAMP)
            {
                $stamp = $etatDepenses[$i]['SOMME'];

            }elseif ($etatDepenses[$i]['RUBRIQUE'] == ConstantsClass::PHOTO) 
            {
                $photo = $etatDepenses[$i]['SOMME'];
            }
        }

        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printEtatFinanceService->print($etatFinance[0], $apee, $computer, $cleanSchool,$medicalBooklet, $stamp, $photo, $schoolYear, $school);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output("State finance", "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Etat finance"), "I"), 200, ['Content-Type' => 'application/pdf']);
        }
        
        
    }

}
