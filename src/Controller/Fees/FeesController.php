<?php

namespace App\Controller\Fees;

use App\Entity\Fees;
use App\Form\FeesType;
use App\Repository\FeesRepository;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route("/fees")]
class FeesController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected FeesRepository $feesRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository, 
        protected SchoolYearRepository $schoolYearRepository,
        )
    { }

    #[Route("/updateFees", name:"fees_updateFees")]
    public function updateFees(Request $request): Response
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
        $schoolYear = $this->schoolYearRepository->find($schoolYear->getId());
        $subSystem = $this->subSystemRepository->find($subSystem->getId());
        $verrou = $mySession->get('verrou');

        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);

        if (!$fees) 
        {
            $fees = new Fees;
        }
        
        $form = $this->createForm(FeesType::class, $fees);
        $form->handleRequest($request);
           
        if($form->isSubmitted() && $form->isValid())
        {
            $fees->setSubSystem($subSystem)
            ->setSchoolYear($schoolYear)
            ;
            $this->em->persist($fees);
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Fees updated with success !'));
            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des frais
            // return $this->redirectToRoute('fees_updateFees', [
            //     'm' => 1
            // ]);
        }

        return $this->render('fees/updateFees.html.twig', [
            'school' => $school,
            'formFees' => $form->createView(),
        ]);
    }
}
