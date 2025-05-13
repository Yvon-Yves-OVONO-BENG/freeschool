<?php

namespace App\Controller\Problems;

use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use App\Repository\LessonRepository;
use App\Repository\SequenceRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route("/problems")]
class ResoudreProblemeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected LessonRepository $lessonRepository,
        protected StudentRepository $studentRepository,
        protected SequenceRepository $sequenceRepository,
        protected EvaluationRepository $evaluationRepository,
    )
    {}

    #[Route("/resoudre-probleme/{slugStudent}/{lessonId}/{sequenceId}", name:"resoudre_probleme")]
    public function resoudreProbleme($slugStudent, $lessonId, $sequenceId)
    {
        #je récupère l'élève
        $student = $this->studentRepository->findOneBy(['slug' => $slugStudent]);

        #je récupère la lesson
        $lesson = $this->lessonRepository->find($lessonId);

        #je récupère la séquence
        $sequence = $this->sequenceRepository->find($sequenceId);

        // Vérifiez si l'évaluation existe déjà
        $evaluation = $this->evaluationRepository->findOneBy([
            'student' => $student,
            'lesson' => $lesson,
            'sequence' => $sequence,
        ]);

        if (!$evaluation) {
            // Créez une nouvelle évaluation avec une note de 0.1
            $evaluation = new Evaluation();
            $evaluation->setStudent($student);
            $evaluation->setLesson($lesson);
            $evaluation->setSequence($sequenceId);
            $evaluation->setMark(0.1);

            $this->em->persist($evaluation);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('The problem is solved with success !'));
        } 
        else 
        {
            $this->addFlash('warning', $this->translator->trans('This evaluation is already exist !'));
        }

        // Redirection vers la page des détails
        return $this->redirectToRoute('details_evaluations_sequence_student', [
            'slugStudent' => $student->getSlug(),
            'sequenceId' => $sequence->getId(),
            'slugClassroom' => $this->getClasseFromEleve($student),
        ]);
    }

    private function getTrimestreFromSequence($sequenceId)
    {
        // Déterminez le trimestre en fonction de la séquence
        if (in_array($sequenceId, [1, 2])) return 1;
        if (in_array($sequenceId, [3, 4])) return 2;
        if (in_array($sequenceId, [5, 6])) return 3;

        throw new \InvalidArgumentException('Séquence invalide.');
    }

    private function getClasseFromEleve($student)
    {
        // Récupérez la classe de l'élève (implémentation selon votre modèle)
        // Exemple basique :
        return $this->studentRepository->findOneBy(['slug' => $student->getSlug()])->getClassroom()->getSlug();
    }
}
