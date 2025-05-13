<?php

namespace App\Controller\Depense;

use App\Entity\Depense;
use App\Form\DepenseType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatDepenseRepository;
use App\Repository\EtatFinanceRepository;
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

#[Route("/depense")]
class SaveDepenseController extends AbstractController
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
        )
    {}

    #[Route("/save-depense", name:"save_depense")]
    public function saveDepense(Request $request): Response
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

        $verrou = $mySession->get('verrou');

        $sessionSchoolYear = $mySession->get('schoolYear');
  
        $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $sessionSchoolYear->getSchoolYear() ]);

        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $depense = new Depense(); 

        $form = $this->createForm(DepenseType::class, $depense);
        $form->handleRequest($request);
        $now = new \DateTime('now');

        if ($form->isSubmitted() && $form->isValid()) 
        {
            // $depense->setCreatedAt($now);
            $depense->setSchoolYear($schoolYear);
            // On ajoute dans la BD
            $this->em->persist($depense);

            // $this->em->persist($etatDepense);

            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Spent saved sucwith success !cessfully'));

            $mySession->set('ajout', 1);

            $depense = new Depense();
            $form = $this->createForm(DepenseType::class, $depense);
            
        }

        $depenses = $this->depenseRepository->findBy([
            'schoolYear' => $schoolYear
        ], ['createdAt' => 'ASC']);

        $numberOfDepenses = count($depenses);

        return $this->render('depense/saveDepense.html.twig', [
            'formDepense' => $form->createView(),
            'numberOfDepenses' => $numberOfDepenses,
            'school' => $school,
            ]);
    }
}
