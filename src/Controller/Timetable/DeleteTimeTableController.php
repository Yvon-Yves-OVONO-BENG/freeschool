<?php

namespace App\Controller\Timetable;

use App\Service\SchoolYearService;
use App\Repository\TimeTableRepository;
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

#[Route("/timetable")]
class DeleteTimeTableController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected TimeTableRepository $timeTableRepository, 
        )
    {}

    #[Route('/delete-time-table/{slug}', name: 'delete_time_table')]
    public function deleteTimeTable(Request $request, string $slug): Response
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

        $timeTable = $this->timeTableRepository->findOneBySlug(['slug' => $slug]);

        $this->em->remove($timeTable);
        $this->em->flush();
        
        $this->addFlash('info', $this->translator->trans('Time table deleted with success !'));
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('display_time_table', [ 's' => 1]);
    }
}
