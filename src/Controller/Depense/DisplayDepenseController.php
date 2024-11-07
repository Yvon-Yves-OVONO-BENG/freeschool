<?php

namespace App\Controller\Depense;

use App\Entity\Depense;
use App\Form\DepenseType;
use App\Service\SchoolYearService;
use App\Repository\DepenseRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
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
class DisplayDepenseController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        protected DepenseRepository $depenseRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/display-depense/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}", name:"display_depense")]
    public function displayDepense(Request $request, int $a = 0, int $m = 0, int $s = 0): Response
    {
        $mySession = $request->getSession();

        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
            $mySession->set('saisiNotes', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            
        }

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

        $depenses = $this->depenseRepository->findBy([
            'schoolYear' => $schoolYear
        ], ['createdAt' => 'ASC']);

        $numberOfDepenses = count($depenses);
        

        return $this->render('depense/displayDepense.html.twig', [
            'depenses' => $depenses,
            'numberOfDepenses' => $numberOfDepenses,
            'school' => $school,
        ]);
    }
}
