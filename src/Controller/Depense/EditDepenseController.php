<?php

namespace App\Controller\Depense;

use App\Form\DepenseType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DepenseRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EtatDepenseRepository;
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
class EditDepenseController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected DepenseRepository $depenseRepository, 
        protected SchoolYearService $schoolYearService,
        protected SchoolYearRepository $schoolYearRepository, 
        protected EtatDepenseRepository $etatDepenseRepository, 
        )
    {}

    #[Route("/edit-depense/{slugDepense}", name:"dedit_depense")]
    public function editDepense(Request $request, string $slugDepense = ""): Response
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

        $depense = $this->depenseRepository->findOneBySlug(['slug' > $slugDepense]);

        $form = $this->createForm(DepenseType::class, $depense);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $this->em->persist($depense);

            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Spent updated with success !'));

            $mySession->set('miseAjour', 1);
            // On se redirige sur la page d'affichage des classes
            return $this->redirectToRoute('display_depense', ['m' => 1]);

        }

        $depenses = $this->depenseRepository->findBy([
            'schoolYear' => $schoolYear
        ], ['createdAt' => 'ASC']);

        $numberOfDepenses = count($depenses);

        return $this->render('depense/saveDepense.html.twig', [
            'formDepense' => $form->createView(),
            'slugDepense' => $slugDepense,
            'depenses' => $depenses,
            'numberOfDepenses' => $numberOfDepenses,
            'school' => $school,
            ]);
    }

}
