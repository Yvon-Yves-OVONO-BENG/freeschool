<?php

namespace App\Controller\Depense;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use App\Service\PrintEtatDepenseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/depense")]
class PrintEtatDepenseController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected DepenseRepository $depenseRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected PrintEtatDepenseService $printEtatDepenseService, 
        )
    {}

    #[Route("/print-etat-depense", name:"print_etat_depense")]
    public function printEtatDepense(Request $request): Response
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

        //je récupère les dépénses 
        $depenses = $this->depenseRepository->findBy(['schoolYear' => $schoolYear]);
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $sommeAPEE = 0;
        $sommeComputer = 0;
        $sommeMedicalBooklet = 0;
        $sommeCleanSchool = 0;
        $sommePhoto = 0;
        $sommeStamp = 0;

        $sommeDepenseParRubrique = $this->depenseRepository->getSumSpendingPerRubrique($schoolYear) ;
        $taille = count($sommeDepenseParRubrique);
        for ($i=0; $i < $taille ; $i++) 
        {
            switch ($sommeDepenseParRubrique[$i]['RUBRIQUE']) 
            {
                case ConstantsClass::APEE:
                    $sommeAPEE = $sommeDepenseParRubrique[$i]['SOMME'];
                    break;

                case ConstantsClass::COMPUTER :
                    $sommeComputer = $sommeDepenseParRubrique[$i]['SOMME'];
                    break;

                case ConstantsClass::MEDICAL_BOOKLET :
                    $sommeMedicalBooklet = $sommeDepenseParRubrique[$i]['SOMME'];
                    break;

                case ConstantsClass::CLEAN_SCHOOL :
                    $sommeCleanSchool = $sommeDepenseParRubrique[$i]['SOMME'];
                    break;

                case ConstantsClass::PHOTO :
                    $sommePhoto = $sommeDepenseParRubrique[$i]['SOMME'];
                    break;

                case ConstantsClass::STAMP :
                    $sommeStamp = $sommeDepenseParRubrique[$i]['SOMME'];
                    break;
            }
            
        }

        $pdf = $this->printEtatDepenseService->print($depenses, $schoolYear, $school, $sommeAPEE, $sommeComputer, $sommeMedicalBooklet, $sommeCleanSchool, $sommePhoto, $sommeStamp );

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output("Expenses state", "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Etat dépense", "I")), 200, ['Content-Type' => 'application/pdf']);
        }
        
        
    }

}
