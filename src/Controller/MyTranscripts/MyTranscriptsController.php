<?php

namespace App\Controller\MyTranscripts;

use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/transcripts")
 */
class MyTranscriptsController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected LessonRepository $lessonRepository,
        protected TeacherRepository $teacherRepository
    )
    {}

    #[Route('/my-transcripts/{slug}', name: 'my_transcripts')]
    public function myTranscripts(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();
        
        #mes variables témoin pour afficher les sweetAlert
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

        $teacher = $this->teacherRepository->findOneBySlug(['slug' => $slug]);

        // on recupère tous les cours de l'enseignant
        $lessons = $this->lessonRepository->findTeacherLessons($teacher);

        return $this->render('my_transcripts/myTranscripts.html.twig', [
            'school' => $school,
            'lessons' => $lessons,
            
        ]);
    }
}
