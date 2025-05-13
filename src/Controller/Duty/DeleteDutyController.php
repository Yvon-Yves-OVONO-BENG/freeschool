<?php

namespace App\Controller\Duty;

use App\Service\SchoolYearService;
use App\Repository\DutyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/duty")]
class DeleteDutyController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected DutyRepository $dutyRepository, 
        )
    {}

    #[Route("/deleteduty/{slug}", name:"duty_deleteDuty")]
    public function deleteDuty(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $duty = $this->dutyRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $this->em->remove($duty);
        $this->em->flush();
            
        $this->addFlash('info', $this->translator->trans('Duty deleted with success !'));
        
        #j'affecte 1 à ma variable pour afficher le message
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('duty_displayDuty', [ 's' => 1]);
        
    }
}
