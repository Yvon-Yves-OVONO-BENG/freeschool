<?php

namespace App\Controller\Subject;

use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SubjectClassesController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected SubjectRepository $subjectRepository, 
        ){}
        
    #[Route('/subject-classes/{slug}', name: 'subject_classes')]
    public function subjectClasses(Request $request, $slug, int $a = 0, int $m = 0, int $s = 0): Response
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
        
        $subjects = $this->subjectRepository->findToDisplay($schoolYear, $subSystem);
        
        $subject = $this->subjectRepository->findOneBySlug(['slug' => $slug]);

        #Lessons où la matière st dispensée
        $lessons = $subject->getLessons();

        return $this->render('subject/subjectClasses.html.twig', [
            'school' => $school,
            'subject' => $subject,
            'subjects' => $subjects,
            'lessons' => $lessons,
        ]);
    }
}
