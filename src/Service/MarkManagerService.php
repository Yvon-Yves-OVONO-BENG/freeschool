<?php

namespace App\Service;

use App\Entity\ConstantsClass;
use App\Entity\Education;
use App\Entity\Skill;
use App\Entity\Lesson;
use App\Entity\Sequence;
use App\Entity\Evaluation;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Repository\LessonRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
// use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class MarkManagerService
{
    public function __construct(
        protected Security $security, 
        protected RequestStack $request,
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected TranslatorInterface $translator, 
        protected SkillRepository $skillRepository, 
        protected LessonRepository $lessonRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    /**
     * Save marks in the database
     *
     * @param Sequence $selectedSequence
     * @param Lesson $selectedLesson
     * @return void
     */
    public function saveMarks(Sequence $selectedSequence, Lesson $selectedLesson, Request $request, String $education, bool $notEvaluated = false)
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->getBag('flashes');

        $now = new DateTime('now');
        $elementIsPersisted = false;

        $numberOfStudents = $request->request->get('numberOfStudents');

        if($notEvaluated == false)
        {
            // Si les notes ont été saisies
            for ($i=1; $i <= $numberOfStudents ; $i++) 
            {
                $mark = $request->request->get('mark'.$i);
                
                // On verifie si la note n'existe pas encore
                $student = $this->studentRepository->find($request->request->get('student'.$i));

                $studentEvaluation = $this->evaluationRepository->findOneBy([
                    'lesson' => $selectedLesson,
                    'sequence' => $selectedSequence,
                    'student' => $student
                ]);
                
                if($studentEvaluation == null) 
                {
                    // Si la note n'existe pas on l'insère
                    $evaluation = new Evaluation();

                    $evaluation->setLesson($selectedLesson)
                        ->setStudent($student)
                        ->setSequence($selectedSequence)
                        ->setMark($mark)
                        ->setCreatedBy($this->security->getUser())
                        ->setCreatedAt($now)
                    ;
                    
                    $this->em->persist($evaluation);

                    // Si c'est la séquence 5 de l'enseignement technique, alors on reconduit la note à la 6ème séquence
                    $numeroSequence = $selectedSequence->getSequence();

                    if($education == ConstantsClass::TECHNICAL_EDUCATION && $numeroSequence == 5)
                    {
                        // On recupère la séquence 6
                        $sequence6 = $this->sequenceRepository->findOneBy(['sequence' => $numeroSequence + 1]);
                        $evaluation6 = new Evaluation();
                        $evaluation6->setLesson($selectedLesson)
                            ->setStudent($student)
                            ->setSequence($sequence6)
                            ->setMark($mark)
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                        ;
                        
                        $this->em->persist($evaluation6);

                    }
    
                    $elementIsPersisted = true;
                    
                } 
            }
            
            // on verifie si la compétence visée n'est pas encore enregistrée
            // $lessonSkill = $this->skillRepository->findOneBy([
            //     'lesson' => $selectedLesson,
            //     'term' => $selectedSequence->getTerm()
            // ]);

            $lessonSkill = $this->skillRepository->findOneBy([
                'lesson' => $selectedLesson,
                'sequence' => $selectedSequence
            ]);
    
            if ($lessonSkill == null) 
            {
                // Si la compétence visée n'est pas encore enregistrée on l'inère
                $skill = new Skill();
                $skill->setLesson($selectedLesson)
                    ->setSequence($selectedSequence)
                    // ->setTerm($selectedSequence->getTerm())
                    ->setSkill($request->request->get('skill'));
    
                $this->em->persist($skill);
    
                $elementIsPersisted = true;
            }

        }else
        {
            // Si la matière est déclarée non evaluée
            $selectedClassroom = $selectedLesson->getClassroom();
            $students = $selectedClassroom->getStudents();

            foreach ($students as $student) 
            {
                // on vérifie si la note n'existe pas encore
                $studentEvaluation = $this->evaluationRepository->findOneBy([
                    'lesson' => $selectedLesson,
                    'sequence' => $selectedSequence,
                    'student' => $student
                ]);
                if($studentEvaluation == null)
                {
                    // Si la note n'existe pas encore on l'ajoute comme non classée
                    $evaluation = new Evaluation();
                    $evaluation->setLesson($selectedLesson)
                        ->setStudent($student)
                        ->setSequence($selectedSequence)
                        ->setMark(ConstantsClass::UNRANKED_MARK)
                        ->setCreatedBy($this->security->getUser())
                        ->setUpdatedBy($this->security->getUser())
                    ;
                    
                    $this->em->persist($evaluation);
    
                    $elementIsPersisted = true;
                }
            }

            // on verifie si la compétence visée n'est pas encore enregistrée
            $lessonSkill = $this->skillRepository->findOneBy([
                'lesson' => $selectedLesson,
                // 'term' => $selectedSequence->getTerm()
                'sequence' => $selectedSequence
            ]);
    
            if ($lessonSkill == null) 
            {
                // Si la compétence visée n'est pas encore enregistrée on insère la double /
                $skill = new Skill();
                $skill->setLesson($selectedLesson)
                    // ->setTerm($selectedSequence->getTerm())
                    ->setSequence($selectedSequence)
                    ->setSkill('//');
    
                $this->em->persist($skill);
    
                $elementIsPersisted = true;
            }
        }      

        if($elementIsPersisted)
        {
            // Si l'enregistrement des notes s'est bien passé, on définit les messages d'information
            $this->em->flush();
            if($notEvaluated == false)
            {
                $flashBag->add('info', $this->translator->trans('Marks saved successfully'));
                $mySession = $this->request->getSession();
                $mySession->set('saisiNotes', 1);
            }else
            {
                $flashBag->add('info', $this->translator->trans('Subject unranked successfully'));
                $mySession = $this->request->getSession();
                $mySession->set('saisiNotes', 1);
            }
        }
    }

    /**
     * Modify a mark in the database
     *
     * @param Request $request
     * @return void
     */
    public function updateMark(int $evaluationId, float $mark, String $education)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $this->request->getSession()->getBag('flashes');

        $now = new DateTime('now');
        $updatedEvaluation = $this->evaluationRepository->find($evaluationId);
        $student = $this->studentRepository->find($updatedEvaluation->getStudent()->getId());
        // dump($student->getFullName());
        
        $lesson = $updatedEvaluation->getLesson()->getSubject();
        
        if($updatedEvaluation !== null) 
        {
            // Si c'est la séquence 5 de l'enseignement technique, alors on recupère l'évaluation 6 pour reconduire la même note
            $numeroSequence = $updatedEvaluation->getSequence()->getSequence();
            // dump($numeroSequence);
            // dump($education);

            if($education == ConstantsClass::TECHNICAL_EDUCATION && $numeroSequence == 5)
            {
                // On recupère la séquence 6
                $sequence6 = $this->sequenceRepository->find($numeroSequence + 1);
                $updatedEvaluation6 = $this->evaluationRepository->findEvaluation($sequence6, $student, $lesson);

                // dd($updatedEvaluation6[0]);

                $updatedEvaluation6[0]->setMark($mark)
                ->setUpdatedBy($this->security->getUser())
                ->setUpdatedAt($now);
                
                $this->em->persist($updatedEvaluation6[0]);

            }

            $updatedEvaluation->setMark($mark)
                ->setUpdatedBy($this->security->getUser())
                ->setUpdatedAt($now);
            
            $this->em->flush();

            $flashBag->add('info', $this->translator->trans('Mark updated successfully'));
            $mySession = $this->request->getSession();
            $mySession->set('saisiNotes', 1);
        }
    }

    
    /**
     * Update a skill
     *
     * @param Request $request
     * @return void
     */
    public function updateSkill(int $skillId, string $skill)
    {
         /**
         * @var FlashBag
         */
        $flashBag = $this->request->getSession()->getBag('flashes');

        $updatedSkill = $this->skillRepository->find($skillId);

        if($updatedSkill !== null)
        {
            $updatedSkill->setSkill($skill);
            
            $this->em->flush();
    
            $flashBag->add('info', $this->translator->trans('Skill updated successfully'));

            $mySession = $this->request->getSession();
            $mySession->set('saisiNotes', 1);
        }
    }


    /**
     * Remove evaluations from the database
     *
     * @param Request $request
     * @return void
     */
    public function removeEvaluations(int $sequenceId, int $lessonId)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $this->request->getSession()->getBag('flashes');

        $elementIsRemoved = false;

        // on recupère la sequence et la lesson concernees
        $sequenceToRemove = $this->sequenceRepository->find($sequenceId);
        $lessonToRemove = $this->lessonRepository->find($lessonId);

        // on recupere les evaluations concernees
        $evaluationsToRemove = $this->evaluationRepository->findBy([
            'lesson' => $lessonToRemove,
            'sequence' => $sequenceToRemove
        ]);

        // On supprime les evaluations en question
        foreach ($evaluationsToRemove as $evaluationToRemove) 
        {
            $elementIsRemoved = true;
            $this->em->remove($evaluationToRemove);
        }

        //Si les notes de l'autre sequence du meme term n'existe pas, on supprime la skill

        //on recupere l'autre sequence du meme term
        $term = $sequenceToRemove->getTerm();
        $sequencesConcerned = $term->getSequences();

        foreach ($sequencesConcerned as $sequence) 
        {
            if($sequence->getId() != $sequenceToRemove->getId())
            {
                $otherSequence = clone $sequence;
            }
        }

        // on recupere les evaluations de cette autre sequence
        $otherevaluationsToRemove = $this->evaluationRepository->findBy([
            'lesson' => $lessonToRemove,
            'sequence' => $otherSequence
        ]);
        
        // si ces notes n'existent pas encore, on supprime la skill
        if(!$otherevaluationsToRemove)
        {
            $skillToRemove = $this->skillRepository->findOneBy([
                'lesson' => $lessonToRemove,
                'term' => $term
            ]);
            
            if($skillToRemove !== null)
            {
                $elementIsRemoved = true;
                $this->em->remove($skillToRemove);
            }
        }

        if($elementIsRemoved)
        {
            $this->em->flush();
            $flashBag->add('info', $this->translator->trans('Marks deleted successfully'));

            $mySession = $this->request->getSession();
            $mySession->set('suppression', 1);
        }
    }

    /**
     * Renew marks 
     *
     * @param Request $request
     * @param Sequence $selectedSequence
     * @param Lesson $selectedLesson
     * @return void
     */
    public function renewMarks(int $newSequenceId, Sequence $selectedSequence, Lesson $selectedLesson)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $this->request->getSession()->getBag('flashes');

        $elementIsPersisted = false;

        $evaluationsToRenew = $this->evaluationRepository->findBy([
            'lesson' => $selectedLesson,
            'sequence' => $selectedSequence
        ]);
        
        $newSequence = $this->sequenceRepository->find($newSequenceId);

        foreach ($evaluationsToRenew as $evaluation) 
        {
            $student = $evaluation->getStudent();

            $studentEvaluation = $this->evaluationRepository->findOneBy([
                'lesson' => $selectedLesson,
                'sequence' => $newSequence,
                'student' => $student
            ]);

            if($studentEvaluation === null)
            {
                $elementIsPersisted = true;

                $newEvaluation = new Evaluation();

                $newEvaluation->setLesson($selectedLesson)
                    ->setSequence($newSequence)
                    ->setStudent($student)
                    ->setMark($evaluation->getMark())
                    ->setCreatedBy($this->security->getUser())
                    ->setUpdatedBy($this->security->getUser())
                ;
    
                $this->em->persist($newEvaluation);
            }
        }

        $oldLessonSkill= $this->skillRepository->findOneBy([
            'lesson' => $selectedLesson,
            'term' => $selectedSequence->getTerm()
        ]);

        $lessonSkill = $this->skillRepository->findOneBy([
            'lesson' => $selectedLesson,
            'term' => $newSequence->getTerm()
        ]);

        if ($lessonSkill === null) 
        {
            $elementIsPersisted = true;

            $skill = new Skill();

            $skill->setLesson($selectedLesson)
                ->setTerm($newSequence->getTerm())
                ->setSkill($oldLessonSkill->getSkill());

            $this->em->persist($skill);
        }

        if($elementIsPersisted)
        {
            $this->em->flush();
            $flashBag->add('info', $this->translator->trans('Marks renewed successfully'));
            $mySession = $this->request->getSession();
            $mySession->set('saisiNotes', 1);
        }else 
        {
            $flashBag->add('info', 'Les notes de '.$selectedLesson->getSubject()->getSubject().' évaluation '.$newSequence->getSequence().' existe déjà');

            $mySession = $this->request->getSession();
            $mySession->set('saisiNotes', 1);
        }
    }

}
