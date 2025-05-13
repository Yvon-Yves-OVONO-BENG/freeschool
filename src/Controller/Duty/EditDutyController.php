<?php

namespace App\Controller\Duty;

use App\Form\DutyType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DutyRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
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

#[Route("/duty")]
class EditDutyController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected DutyRepository $dutyRepository, 
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route("/editDuty/{slug}", name:"duty_editDuty")]
    public function saveDuty(Request $request, string $slug): Response
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
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $duty = $this->dutyRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(DutyType::class, $duty);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $duty->setDuty(strtoupper($duty->getDuty()));

            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Duty updated with success !'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des diplomes
            return $this->redirectToRoute('duty_displayDuty', [ 'm' => 1]);
            
        }

        $dutys = $this->dutyRepository->findBy([], ['duty' => 'ASC']);

        return $this->render('duty/saveDuty.html.twig', [
            'formDuty' => $form->createView(),
            'slug' => $slug,
            'dutys' => $dutys,
            'school' => $school,
            ]);
    }

}
