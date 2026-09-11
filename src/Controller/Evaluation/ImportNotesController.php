<?php

namespace App\Controller\Evaluation;

use DateTime;
use App\Entity\Classroom;
use App\Entity\Evaluation;
use App\Entity\Skill;
use App\Service\StrService;
use App\Service\ImportExcel;
use App\Service\QrcodeService;
use App\Service\StudentService;
use App\Service\ReportRefreshService;
use App\Repository\SexRepository;
use App\Service\ClassroomService;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\RepeaterRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SubSystemRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class ImportNotesController extends AbstractController
{
    public function __construct(
        private StrService $strService,
        private EntityManagerInterface $em,
        private SexRepository $sexRepository,
        private QrcodeService $qrcodeService,
        private StudentService $studentService,
        private TranslatorInterface $translator,
        private SchoolRepository $schoolRepository,
        private ClassroomService $classroomService,
        private StudentRepository $studentRepository, 
        private RepeaterRepository $repeaterRepository,
        private ClassroomRepository $classroomRepository,
        private SubSystemRepository $subSystemRepository,
        private ImportExcel $importExcel, 
        private EvaluationRepository $evaluationRepository, 
        private SequenceRepository $sequenceRepository, 
        private LessonRepository $lessonRepository,
        private SchoolYearRepository $schoolYearRepository,
        private ReportRefreshService $reportRefreshService,
        )
    {}

    #[Route('/import-note/{slugTeacher}', name: 'import_note')]
    public function importNote(Request $request, ?string $slugTeacher = null,): Response
    {
        $mySession = $request->getSession();
        
        if(!$this->getUser())
        {
            return $this->redirectToRoute("app_logout");
        }

        if ($request->request->has('importNotes')) 
        {
            $sequenceId = $request->request->get('sequence');
            $lessonId = $request->request->get('lesson');

            //je récupère l'évaluation
            $evaluations = $this->evaluationRepository->findBy([
                'sequence' => $this->sequenceRepository->find($sequenceId), 
                'lesson' => $this->lessonRepository->find($lessonId)
            ]);
            
            //je vérifie si l'evaluation existe déjà
            if (count($evaluations) == 0) 
            {
                //nouvelle skill
                $skill = new Skill;

                $skill
                ->setLesson($this->lessonRepository->find($lessonId))
                ->setSequence($this->sequenceRepository->find($sequenceId))
                ->setSkill("//")
                ;

                $this->em->persist($skill);

                // je récupère le fichier
                $file = $request->files->get('excelfile');
                
                if ($file) 
                {
                    //je récupère l'extension
                    $extension = $file->getClientOriginalExtension();
                    $mimeType = $file->getMimeType();

                    //je verifie l'extension
                    if (!in_array($extension, ['xls', 'xlsx']) || 
                    !in_array($mimeType, ['application/vnd.ms-excel', 
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) 
                    {
                        $this->addFlash('info', $this->translator->trans('This file is not an Excel file !'));
            
                        $mySession->set('suppression', 1);
                    }

                    // Sauvegarder le fichier temporairement
                    $filePath = $file->getPathname();

                    // Importer les données
                    $datas = $this->importExcel->import($filePath);

                    $students = $this->lessonRepository->find($lessonId)->getClassroom()->getStudents()->toArray();
            
                    usort($students, function($a, $b) 
                    {
                        return strcmp($a->getFullName(), $b->getFullName());
                    });
                
                    foreach ($datas as $data) 
                    {
                        $evaluation = new Evaluation;

                        foreach ($students as $student) 
                        {
                            if($student->getFullName() == $data[2])
                            {
                                // if ($data[3] < 0 || $data[3] > 20 ) 
                                // {
                                //     $this->addFlash('info',  $this->translator->trans("Erreur : Note '{$data[3]}' for student {$data[2]} is invalid (must be between 0 and20)"));
                    
                                //     $mySession->set('suppression', 1);
                                // } 
                                // else 
                                // {
                                    $evaluation
                                    ->setLesson($this->lessonRepository->find($lessonId))
                                    ->setStudent($student)
                                    ->setMark($data[3])
                                    ->setSequence($this->sequenceRepository->find($sequenceId))
                                    ->setCreatedBy($this->getUser())
                                    ->setCreatedAt(new DateTime('today'))
                                    ;
                                // }

                                $this->em->persist($evaluation);
                            }
                        }
                    }

                    $this->em->flush();
                    $this->reportRefreshService->refreshAfterSequence(
                        $this->sequenceRepository->find($sequenceId),
                        $this->lessonRepository->find($lessonId)->getClassroom()
                    );
                
                    $this->addFlash('info',  $this->translator->trans('Import successfuly completed !'));
                    
                    $mySession->set('saisiNotes', 1);

                }
            } 
            else 
            {
                $this->addFlash('info',  $this->translator->trans('Notes is already exists !'));
                    
                $mySession->set('suppression', 1);

                return $this->redirectToRoute('evaluation_markRecorder', [ 'slugTeacher' => $slugTeacher, 's' => 1]);
            }
            
        }

        return $this->redirectToRoute('evaluation_markRecorder', [ 'slugTeacher' => $slugTeacher]);
    }
}
