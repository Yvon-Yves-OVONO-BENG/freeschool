<?php

namespace App\Controller\Progress;

use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/progress")]
class DisplayLessonPlainController extends AbstractController
{
    public function __construct(
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}


    #[Route("/display-lesson-plain/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}", name:"display_lesson_plain")]
    public function displayLessonPlain(Request $request, int $a = 0, int $m = 0, int $s = 0): Response
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
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        /**
         * @var User
         */
        $user = $this->getUser();
        $teacher = $user->getTeacher();

        // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $lessonPrevus = $this->lessonRepository->findBy([
            'teacher' => $teacher,
        ]);
        
        return $this->render('progress/displayLessonPlain.html.twig', [
            'lessonPrevus' => $lessonPrevus,
            'school' => $school,

        ]);
    }

}
