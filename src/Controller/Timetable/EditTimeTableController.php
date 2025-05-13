<?php

namespace App\Controller\Timetable;

use DateTimeImmutable;
use App\Entity\TimeTable;
use App\Form\TimeTableType;
use App\Repository\SchoolRepository;
use App\Repository\TimeTableRepository;
use App\Repository\SchoolYearRepository;
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

/**
 * @Route("/timetable")
 */
class EditTimeTableController extends AbstractController
{
    public function __construct(protected TimeTableRepository $timeTableRepository, protected EntityManagerInterface $em, protected TranslatorInterface $translator, protected SchoolYearRepository $schoolYearRepository, protected SchoolRepository $schoolRepository,)
    {}
    
    #[Route('/edit-time-table/{slug}', name: 'edit_time_table')]
    public function editTimeTable(Request $request, string $slug): Response
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

        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $timeTable = $this->timeTableRepository->findOneBySlug(['slug' => $slug]);

        $form = $this->createForm(TimeTableType::class, $timeTable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {

            $timeTable
                ->setStartTime($request->get('startTime'))
                ->setEndTime($request->get('endTime'))
                ->setSchoolYear($schoolYear)
                ->setSubSystem($subSystem)
                ;

            $this->em->persist($timeTable);
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans("Time table updated with success ! !"));
            $mySession->set('miseAjour', 1);

            // on initialise le formulaire
            $timeTable = new TimeTable;
            $form = $this->createForm(TimeTableType::class, $timeTable);
            
            
        }
        
        return $this->render('timetable/saveTimeTable.html.twig', [
            'timeTableForm' => $form->createView(),
            'slug' => $slug,
            'school' => $school,
        ]);
    }
}
