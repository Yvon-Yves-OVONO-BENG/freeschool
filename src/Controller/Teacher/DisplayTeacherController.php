<?php

namespace App\Controller\Teacher;

use App\Entity\Term;
use App\Entity\Level;
use App\Entity\Sequence;
use App\Service\TeacherService;
use App\Repository\TermRepository;
use App\Repository\LevelRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/teacher")]
class DisplayTeacherController extends AbstractController
{
    public function __construct(
        protected TeacherService $teacherService, 
        protected TermRepository $termRepository, 
        protected LevelRepository $levelRepository,
        protected SchoolRepository $schoolRepository,
        protected SequenceRepository $sequenceRepository, 
        protected TeacherRepository $teacherRepository, 
        )
    {}

    #[Route("/displayTeacher/{displayLaters}/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}/{rPwd<[0-1]{1}>}", name:"teacher_displayTeacher")]
    public function displayTeacher(Request $request, bool $displayLaters = false, int $a = 0, int $m = 0, int $s = 0, int $rPwd = 0): Response
    {
        $mySession = $request->getSession();

        if ($a == 1 || $m == 0 || $s == 0 || $rPwd == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            $mySession->set('resetPwd', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
            $mySession->set('saisiNotes', null);
            $mySession->set('resetPwd', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            $mySession->set('resetPwd', null);
            
        }

        #je teste si c'est la regeneration du mot de passe
        if ($rPwd == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            $mySession->set('resetPwd', 1);
            
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

        // on recupère les enseignants à afficher
        $teachers = $this->teacherRepository->findAllToDisplay($schoolYear, $subSystem);

        $evaluations = [];
        $selectedLevel = new Level();
        $selectedTerm = new Term();
        $selectedSequence = new Sequence();

        $termId = $request->request->get('term');
        $sequenceId = $request->request->get('sequence');
        
        if($displayLaters == true)
        {  
            if($sequenceId !== null)
            {
                $evaluations = $this->teacherService->getUnrecordedMark($sequenceId, $request->request->get('level'));
                
                $selectedSequence = $this->sequenceRepository->find($sequenceId);
                $selectedLevel = $this->levelRepository->find($request->request->get('level')); 

            } else 
            {
                $displayLaters = false;
            }
        }
        
        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);
        $sequences = $this->sequenceRepository->findBy([], ['sequence' => 'ASC']);
        $levels =  $this->levelRepository->findBy([], ['level' => 'ASC']);

        return $this->render('teacher/displayTeacher.html.twig', [
            'teachers' => $teachers,
            'terms' => $terms,
            'levels' => $levels,
            'sequences' => $sequences,
            'evaluations' => $evaluations,
            'selectedTerm' => $selectedTerm,
            'selectedSequence' => $selectedSequence,
            'selectedLevel' => $selectedLevel,
            'displayLaters' => $displayLaters,
            'school' => $school,
        ]);
    }

    
}
