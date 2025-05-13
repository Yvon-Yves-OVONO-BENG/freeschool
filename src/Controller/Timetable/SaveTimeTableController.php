<?php

namespace App\Controller\Timetable;

use DateTimeImmutable;
use App\Entity\TimeTable;
use App\Form\TimeTableType;
use App\Repository\SchoolRepository;
use App\Repository\TimeTableRepository;
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

 #[Route("/timetable")]
class SaveTimeTableController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected TimeTableRepository $timeTableRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route('/save-time-table', name: 'save_time_table')]
    public function saveTimeTable(Request $request): Response
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

        $slug = 0;

        $timeTable = new TimeTable;

        $form = $this->createForm(TimeTableType::class, $timeTable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $classroom = $form->getData()->getClassroom();
            $day = $form->getData()->getDay();
            $subject = $form->getData()->getSubject();
            $teacher = $form->getData()->getTeacher();
            $startTime = $request->get('startTime');
            $endTime = $request->get('endTime');

            // $endTime = $form->getData()->getEndTime();
            // $startTime = $form->getData()->getStartTime();
            // $endTime = $form->getData()->getEndTime();
            // $startTime = new DateTimeImmutable($request->get('startTime'));
            // $endTime = new DateTimeImmutable($request->get('endTime'));

            // $startTime = $startTime->format('H:i:s');
            // $endTime = $endTime->format('H:i:s');

            // dump($startTime);
            // dd($endTime);

            $findTimeTable1 = $this->timeTableRepository->findBy([
                'classroom' => $classroom,
                'day' => $day,
                'startTime' => $startTime,
                'subject' => $subject,
                'teacher' => $teacher,
            ]);

            $findTimeTable2 = $this->timeTableRepository->findBy([
                'classroom' => $classroom,
                'day' => $day,
                'endTime' => $endTime,
                'subject' => $subject,
                'teacher' => $teacher,
            ]);

            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait la derniere matiere de la table
            $dernierTimeTable = $this->timeTableRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierTimeTable) 
            {
                $id = $dernierTimeTable[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            if ($findTimeTable1 || $findTimeTable2) 
            {
                $this->addFlash('info', $this->translator->trans("A time slot is already occupied"));
                $mySession->set('suppression', 1);
                // on initialise le formulaire
                $timeTable = new TimeTable;
                $form = $this->createForm(TimeTableType::class, $timeTable);
            }else
            {
                $subSyste = $this->subSystemRepository->find($subSystem->getId());
                $timeTable
                ->setStartTime($request->get('startTime'))
                ->setEndTime($request->get('endTime'))
                ->setSchoolYear($schoolYear)
                ->setSubSystem($subSyste)
                ->setSlug($slug.$id)
                ;
                
                $this->em->persist($timeTable);
                $this->em->flush();

                $this->addFlash('info', $this->translator->trans("Time table saved with success ! !"));
                $mySession->set('ajout', 1);

                // on initialise le formulaire
                $timeTable = new TimeTable;
                $form = $this->createForm(TimeTableType::class, $timeTable);
            }
            
        }

        $slug = 0;
        
        return $this->render('timetable/saveTimeTable.html.twig', [
            'slug' => $slug,
            'school' => $school,
            'timeTableForm' => $form->createView(),
        ]);
    }
}
