<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\Conseil;
use App\Repository\TermRepository;
use App\Repository\ConseilRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class ConseilManagerService
{
    public function __construct(
        protected Security $security,  
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected StudentRepository $studentRepository, 
        protected ConseilRepository $conseilRepository, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    /**
     * Save conseils in the database
     *
     * @param Sequence $selectedSequence
     * @param Lesson $selectedLesson
     * @return void
     */
    public function saveConseils(Term $selectedTerm, Request $request)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $elementIsPerssited = false;

        $numberOfStudents = $request->request->get('numberOfStudents');
                
        for ($i=1; $i <= $numberOfStudents ; $i++) 
        {
            $decision = $request->request->get('decision'.$i);
            $motif = $request->request->get('motif'.$i);

            $student = $this->studentRepository->find($request->request->get('student'.$i));

            // On verifie si l'conseil n'existe pas encore
            $studentConseil = $this->conseilRepository->findOneBy([
                'term' => $selectedTerm,
                'student' => $student
            ]);
            
            if($studentConseil == null) // Si l'conseil n'existe pas on l'insère
            {
                $conseilToSave = new Conseil();

                $conseilToSave->setTerm($selectedTerm)
                    ->setStudent($student)
                    ->setDecision($decision)
                    ->setMotif($motif)
                    ->setCreatedBy($this->security->getUser())
                    ->setCreatedAt(new DateTime('now'))
                    ->setUpdatedAt(new DateTime('now'))
                    ;
                    
                $this->em->persist($conseilToSave);
                $elementIsPerssited = true;
               
            } 
            
        }
            
        if($elementIsPerssited)
        {
            $this->em->flush();
        }
    }

    /**
     * Update a conseil
     *
     * @param integer $conseilId
     * @param integer $conseil
     * @return void
     */
    public function updateConseil(int $conseilId, string $decision, string $motif,Request $request)
    {
         /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $elementIsPerssited = false;

        $updatedConseil = $this->conseilRepository->find($conseilId);

        // Si l'conseil existe dejà, alors on la met à jour
        if($updatedConseil !== null) 
        {
            $updatedConseil
                ->setDecision($decision)
                ->setMotif($motif)
                ->setUpdatedAt(new DateTime('now'))
                ->setUpdatedBy($this->security->getUser());

            $this->em->persist($updatedConseil);

            $elementIsPerssited = true;
        }
        
        if($elementIsPerssited)
        {
            $this->em->flush();

        }
    }


    /**
     * remove all conseils for a giving classroom and term
     *
     * @param integer $termId
     * @param integer $classroomId
     * @return void
     */
    public function removeConseils(int $termId, int $classroomId, Request $request)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $selectedTerm = $this->termRepository->find($termId);
        $selectedClassroom = $this->classroomRepository->find($classroomId);

        $elementIsRemoved = false;

        // on recupere les conseils concernees
        $conseilsToRemove = $this->conseilRepository->findConseils($selectedTerm, $selectedClassroom);

        // On supprime les conseils en question
        foreach ($conseilsToRemove as $conseilToRemove) 
        {
            $this->em->remove($conseilToRemove);
            $elementIsRemoved = true;
        }

        if($elementIsRemoved)
        {
            $this->em->flush();
        }
    }

}