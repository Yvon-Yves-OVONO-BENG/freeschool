<?php

namespace App\Controller\Evaluation;

use App\Entity\Skill;
use App\Entity\ConstantsClass;
use App\Service\SequenceService;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Service\MarkManagerService;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\VerrouReportRepository;
use App\Repository\VerrouSequenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/evaluation")]
class MarkRecorderController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected SkillRepository $skillRepository, 
        protected SequenceService $sequenceService, 
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected TeacherRepository $teacherRepository, 
        protected MarkManagerService $markManagerService, 
        protected SequenceRepository $sequenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected VerrouReportRepository $verrouReportRepository, 
        protected VerrouSequenceRepository $verrouSequenceRepository, 
        )
    {}

    #[Route("/markRecorder/{slugTeacher}/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}/{deleteAllMarks}/{sequenceSelectionnee}/{lessonSelectionnee}", name:"evaluation_markRecorder")]
    public function markRecorder(Request $request, string $slugTeacher = null, int $a = 0, int $m = 0, 
    int $s = 0, int $deleteAllMarks = 0, int $sequenceSelectionnee = 0, int $lessonSelectionnee = 0): Response
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

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }

        // dd($request->request->get('teacher'));
        // $id = $user->getTeacher()->getId();
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);
        $education = $school->getEducation()->getEducation();

        // on recupère les trimestres
        $term1 = $this->termRepository->findOneByTerm(1);
        $term2 = $this->termRepository->findOneByTerm(2);
        $term3 = $this->termRepository->findOneByTerm(3);
        $term0 = $this->termRepository->findOneByTerm(0);

        // on recupère les verrouReport pour utiliser leurs états dans la requete des terms
        $term1IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term1
        ])->isVerrouReport();
        $term2IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term2
        ])->isVerrouReport();
        $term3IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term3
        ])->isVerrouReport();
        $term0IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term0
        ])->isVerrouReport();


        // on recupère les six sequences pour recupérer les verrouReport liés aux trimestres
        $sequence1 = $this->sequenceRepository->findOneBySequence(1);
        $sequence2 = $this->sequenceRepository->findOneBySequence(2);
        $sequence3 = $this->sequenceRepository->findOneBySequence(3);
        $sequence4 = $this->sequenceRepository->findOneBySequence(4);
        $sequence5 = $this->sequenceRepository->findOneBySequence(5);
        $sequence6 = $this->sequenceRepository->findOneBySequence(6);

        $sequence1IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence1
        ])->isVerrouSequence();

        $sequence2IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence2
        ])->isVerrouSequence();

        $sequence3IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence3
        ])->isVerrouSequence();

        $sequence4IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence4
        ])->isVerrouSequence();

        $sequence5IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence5
        ])->isVerrouSequence();

        $sequence6IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence6
        ])->isVerrouSequence();

        // on recupère les séquences à afficher
        $sequences = $this->sequenceRepository->findForMark($term1IsLocked, $term2IsLocked, $term3IsLocked, $sequence1IsLocked, $sequence2IsLocked, $sequence3IsLocked, $sequence4IsLocked, $sequence5IsLocked, $sequence6IsLocked);

        // On enlève la séquence 6 de la liste s'il s'agit de l'enseignement technique
        $sequences = $this->sequenceService->removeSequence6($sequences, $schoolYear);
        
        // On recupère l'enseignant qui dispense
        $teacher = $this->teacherRepository->findOneBySlug(['slug' => $slugTeacher]);

        // on recupère tous les cours de l'enseignant
        $lessons = $this->lessonRepository->findTeacherLessons($teacher);

        $skillToUpdate = null;
        $evaluationToUpdate = null;

        if($request->request->has('markToUpdate'))
        {
            // on recupère l'evaluation à modifier s'il sagit d'une demande de modification de note
            $evaluationToUpdate = $this->evaluationRepository->find($request->request->get('evaluation'));

        }

        if ($request->request->has('skillToUpdate')) 
        {
            // on recupère la compétence à modifier s'il sagit d'une demande de modification de la compétence
            // $skillToUpdate = $this->skillRepository->find($request->request->get('oldSkill'));
            $skillToUpdate = $this->skillRepository->findOneBy([
                'sequence' => $request->request->get('sequence'),
                'lesson' => $request->request->get('lesson'),
                'skill' => $request->request->get('oldSkill')
            ]);

            if ($skillToUpdate == null) 
            {
                // Si la compétence visée n'est pas encore enregistrée on l'inère
                $skill = new Skill();
                $skill->setLesson($this->lessonRepository->find($request->request->get('lesson')))
                    ->setSequence($this->sequenceRepository->find($request->request->get('sequence')))
                    // ->setTerm($selectedSequence->getTerm())
                    ->setSkill("//");
    
                $this->em->persist($skill);
                $this->em->flush();


                $skillToUpdate = $this->skillRepository->findOneBy([
                    'sequence' => $request->request->get('sequence'),
                    'lesson' => $request->request->get('lesson'),
                    'skill' => $request->request->get('oldSkill')
                ]);

            } 
            
        }
        
        if($request->request->has('sequence') || $deleteAllMarks == 1)
        {   
            if ($deleteAllMarks == 1) 
            {
                $sequenceId = $sequenceSelectionnee;
                $lessonId = $lessonSelectionnee;
            } 
            else 
            {
                $sequenceId = (int)$request->request->get('sequence');
                $lessonId = (int)$request->request->get('lesson');
            }
            
            $selectedLesson = $this->lessonRepository->find($lessonId);
            $selectedSequence = $this->sequenceRepository->find($sequenceId);
            
            if ($request->request->has('saveMark')) 
            {  
                // On enregistre les notes si le bouton "Enregistrer" est cliqué
                $this->markManagerService->saveMarks($selectedSequence, $selectedLesson, $request, $education);
                
            }
            elseif($request->request->has('saveNotEvaluatedMark'))
            {
                // On enregistre les notes à non classé pour toute la classe si le bouton "Non évalué" est cliqué
                $this->markManagerService->saveMarks($selectedSequence, $selectedLesson, $request, $education, true);

            }
            elseif($request->request->has('updateMark'))
            { 
                // on modifie la note demandée
                $this->markManagerService->updateMark($request->request->get('evaluationToUpdate'), $request->request->get('mark'), $education);

            }
            elseif($request->request->has('updateSkill'))
            {
                // On modifie la compétence visée demandée
                $this->markManagerService->updateSkill($request->request->get('oldSkillId'), $request->request->get('newSkill'));

            }
            // elseif ($request->request->has('removeAllMarks'))
            elseif ($deleteAllMarks == 1)
            {
                // On supprime toutes les notes de l'évaluation et du cours choisis
                $this->markManagerService->removeEvaluations($sequenceSelectionnee, $lessonSelectionnee);

            }
            elseif($request->request->has('renewMark'))
            {
                // on reconduit les notes d'une évaluation à celle demandée
                $newSequenceId = $request->request->get('newSequence');

                $this->markManagerService->renewMarks($newSequenceId, $selectedSequence, $selectedLesson);

                $selectedSequence = $this->sequenceRepository->find($newSequenceId);

            }

            // on recupère les notes à afficher
            $evaluations = $this->evaluationRepository->findEvaluations($sequenceId, $lessonId);
            
            // on recupère les élèves de la classe à afficher
            $students = $this->studentRepository->findBy([
                'classroom' => $selectedLesson->getClassroom(),
                'schoolYear' => $schoolYear
            ], [
                'fullName' => 'ASC'
                ]);
            
                // on recupère la compétence visée à afficher
            $skill = $this->skillRepository->findOneBy([
                'lesson' => $selectedLesson,
                // 'term' => $selectedSequence->getTerm()
                'sequence' => $selectedSequence
                ]);

            return $this->render('evaluation/markRecorder.html.twig', [
                'sequences' => $sequences,
                'lessons' => $lessons,
                'teacher' => $teacher,
                'selectedSequence' => $selectedSequence,
                'selectedLesson' => $selectedLesson,
                'evaluations' => $evaluations,
                'skill' => $skill,
                'students' => $students,
                'evaluationToUpdate' => $evaluationToUpdate,
                'skillToUpdate' => $skillToUpdate,
                'unrankedMark' => ConstantsClass::UNRANKED_MARK,
                'school' => $school,
            ]);
        }
        
        return $this->render('evaluation/markRecorder.html.twig', [
            'sequences' => $sequences,
            'lessons' => $lessons,
            'teacher' => $teacher,
            'school' => $school,
        ]);
    }

}
