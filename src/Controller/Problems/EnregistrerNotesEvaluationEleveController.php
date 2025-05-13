<?php

namespace App\Controller\Problems;

use DateTime;
use App\Entity\Evaluation;
use App\Repository\LessonRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route("/problems")]
class EnregistrerNotesEvaluationEleveController extends AbstractController
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

    #[Route("/enregistrer-notes-evaluation-eleve/{slugStudent}/{sequenceId}/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}", name:"enregistrer_notes_evaluation_eleve")]
    public function enregistrerNotesEvaluationEleve(Request $request, $slugStudent, $sequenceId, int $a = 0, int $m = 0, int $s = 0)
    {
        $maSession = $request->getSession();

        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout',null);
            $maSession->set('suppression', null);
            $maSession->set('miseAjour', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $maSession->set('ajout', null);
            $maSession->set('suppression', null);
            $maSession->set('miseAjour', 1);
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $maSession->set('ajout',null);
            $maSession->set('suppression', 1);
            $maSession->set('miseAjour', null);
        }
        
        if($maSession)
        {
            $schoolYear = $maSession->get('schoolYear');
            $subSystem = $maSession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }

        #je récupère l'élève
        $student = $this->studentRepository->findOneBy(['slug' => $slugStudent]);

        #je récupère la séquence
        $sequence = $this->sequenceRepository->find($sequenceId);

        // dump($evaluations);
        
        # je récupère les données du formulaire
        $datas = $request->request->all();

        #je parcours mes données
        foreach ($datas as $key => $value) 
        {
            if(preg_match('/^evaluationId(\d+)$/', $key, $matches))
            { 
                # je récupère l'index
                $index = $matches[1];

                #je récupère l'id de l'évaluation
                $evaluationId = $value;

                #Je construit la clé correspondante
                $noteKey = "mark".$index;

                if(isset($datas[$noteKey]))
                {
                    #je récupère la note
                    $noteValue = $datas[$noteKey];

                    $evaluation = $this->evaluationRepository->find($evaluationId);
                    #jes teste les evaluationId
                    if ($evaluation) 
                    {
                        $evaluation->setMark($noteValue)
                                    ->setUpdatedBy($this->getUser())
                                    ->setUpdatedAt(new DateTime('now'));

                        $this->em->persist($evaluation);
                    }
                }
                
            }
            
        }
        
        $this->em->flush();
       
        $this->addFlash('info', $this->translator->trans('Mark save with success !'));
                
        $maSession->set('ajout', 1);

        // Redirection vers la page des détails
        return $this->redirectToRoute('details_evaluations_sequence_student', [
            'slugStudent' => $student->getSlug(),
            'sequenceId' => $sequence->getId(),
            'slugClassroom' => $this->getClasseFromEleve($student),
            'm' => 1
        ]);
    }

    private function getClasseFromEleve($student)
    {
        // Récupérez la classe de l'élève (implémentation selon votre modèle)
        // Exemple basique :
        return $this->studentRepository->findOneBy(['slug' => $student->getSlug()])->getClassroom()->getSlug();
    }
}
