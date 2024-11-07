<?php

namespace App\Controller\Depense;

use App\Entity\ConstantsClass;
use App\Service\SchoolYearService;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatDepenseRepository;
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

/**
* @Route("/depense")
*/
class DeleteDepenseController extends AbstractController
{
    public function __construct(protected DepenseRepository $depenseRepository, protected EntityManagerInterface $em, protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator, protected SchoolYearRepository $schoolYearRepository, protected EtatDepenseRepository $etatDepenseRepository)
    {
    }

    /**
     * @Route("/delete-depense/{id<[0-9]+>}", name="delete_depense")
     */
    public function deleteDepense(Request $request, int $id): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');
            $sessionSchoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');

       
        $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $sessionSchoolYear->getSchoolYear() ]);

        //je récupère l'etat financier
        $etatDepense = $this->etatDepenseRepository->findOneBy(['schoolYear' => $schoolYear]);

        //je récupère mes totaux
        $apeeFees = $etatDepense->getApeeFees();
        $computerFees = $etatDepense->getComputerFees();
        $medicalBookletFees = $etatDepense->getMedicalBookletFees();
        $cleanSchoolFees = $etatDepense->getCleanSchoolFees();
        $photoFees = $etatDepense->getPhotoFees();
        $stampFees = $etatDepense->getStampFees();
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $depense = $this->depenseRepository->find($id);

        $rubrique = $depense->getRubrique()->getRubrique();
            
            switch ($rubrique) {
                case ConstantsClass::APEE :
                    $noueavMontantApee = $apeeFees - $depense->getMontant();
                    $etatDepense->setApeeFees($noueavMontantApee);

                    break;

                case ConstantsClass::COMPUTER :
                    $noueavMontantComputer = $computerFees - $depense->getMontant();
                    $etatDepense->setComputerFees($noueavMontantComputer);

                    break;

                case ConstantsClass::MEDICAL_BOOKLET :
                    $noueavMontantMedicalBooklet = $medicalBookletFees - $depense->getMontant();
                    $etatDepense->setMedicalBookletFees($noueavMontantMedicalBooklet);
            
                    break;


                    case ConstantsClass::CLEAN_SCHOOL :
                        $noueavMontantCleanSchool = $cleanSchoolFees - $depense->getMontant();
                        $etatDepense->setCleanSchoolFees($noueavMontantCleanSchool);
                        break;
    
                    case ConstantsClass::PHOTO :
                        $noueavMontantPhoto = $photoFees - $depense->getMontant();
                        $etatDepense->setPhotoFees($noueavMontantPhoto);
                        break;
    
                    case ConstantsClass::STAMP :
                        $noueavMontantStamp = $stampFees - $depense->getMontant();
                        $etatDepense->setStampFees($noueavMontantStamp) ;
                        break;
                
            }

        $this->em->remove($depense);
        $this->em->persist($etatDepense);
        $this->em->flush();
            
        $this->addFlash('info', $this->translator->trans('Spent deleted successfully'));
        
        return $this->redirectToRoute('display_depense',
        [ 'notification' => 2]);
    }
}
