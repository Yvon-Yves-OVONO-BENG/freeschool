<?php

namespace App\Controller\EtatFinance;

use App\Entity\ConstantsClass;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatDepenseRepository;
use App\Repository\EtatFinanceRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/etat-finance")]
class EtatFinanceController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        protected DepenseRepository $depenseRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected EtatFinanceRepository $etatFinanceRepository, 
        protected EtatDepenseRepository $etatDepenseRepository, 
        protected RegistrationRepository $registrationRepository, 
        )
    {}

    #[Route("/finance", name:"etat_finance")]
    public function etatFinance(Request $request): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        $sessionSchoolYear = $mySession->get('schoolYear');
  
        $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $sessionSchoolYear->getSchoolYear() ]);

        //je récupère l'etat financier
        $etatFinance = $this->registrationRepository->getEtatFinancier($schoolYear);

        //je récupère l'etat des dépenses
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
        

        // dd($etatDepenses[0]);
        return $this->render('etat_finance/displayEtatFinance.html.twig', [
            'etatFinance' => $etatFinance[0],
            'apee' => $apee,
            'computer' => $computer,
            'cleanSchool' => $cleanSchool,
            'medicalBooklet' => $medicalBooklet,
            'stamp' => $stamp,
            'photo' => $photo,
            'school' => $school,
        ]);
    }
}
