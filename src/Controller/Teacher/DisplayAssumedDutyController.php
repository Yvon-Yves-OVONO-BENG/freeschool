<?php

namespace App\Controller\Teacher;

use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/teacher")]
class DisplayAssumedDutyController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        )
    {}

    #[Route("/displayAssumedDuty", name:"teacher_displayAssumedDuty")]
    public function displayAssumedDuty(Request $request): Response
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

        $teachers = $this->teacherRepository->findAllToDisplay($schoolYear, $subSystem );

        return $this->render('teacher/displayAssumedDuty.html.twig', [
            'teachers' => $teachers,
            'school' => $school,
        ]);
    }
}