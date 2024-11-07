<?php

namespace App\Controller\Teacher;

use App\Repository\TeacherRepository;
use App\Repository\EvaluationRepository;
use App\Repository\UserRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/teacher")]
class DeleteTeacherController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected UserRepository $userRepository, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected TeacherRepository $teacherRepository,  
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    #[Route("/deleteTeacher/{slug}", name:"teacher_deleteTeacher")]
    public function deleteTeacher(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        #mes variables témoin pour afficher les sweetAlert
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

        $teacher = $this->teacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        if(count($teacher->getLessons()))
        {
            $this->addFlash('info',  $this->translator->trans('Deleting denied. This teacher has lessons'));

            $mySession->set('suppression', 1);

            return $this->redirectToRoute('teacher_displayTeacher',
            [   'displayLaters' => 0,
                's' => 1
            ]);
        }else 
        {
            if($user = $this->userRepository->findOneBy(['teacher' => $teacher]))
            {
                $this->em->remove($user);
            }

            $teacherEvaluationsRecorded = $this->evaluationRepository->findBy([
                'createdBy' => $teacher
            ]);

            foreach ($teacherEvaluationsRecorded as $evaluation) 
            {
                $evaluation->setCreatedBy(null)
                    ->setUpdatedBy(null);

                $this->em->persist($evaluation);
            }

            // Si l'enseignant a eu a saisir les notes, on efface ses traces dans evaluations

            $this->em->remove($teacher);
            $this->em->flush();
            
            $this->addFlash('info',  $this->translator->trans('Staff deleted successfully'));

            $mySession->set('supression', 1);

            return $this->redirectToRoute('teacher_displayTeacher',
            [ 'displayLaters' => 0,
                's' => 1
            ]);
        }

        
    }
}