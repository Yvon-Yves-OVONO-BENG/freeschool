<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Repository\DutyRepository;
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

#[Route("/register_and_list")]
class AbsenceReportController extends AbstractController
{
    public function __construct(
        protected DutyRepository $dutyRepository, 
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        )
    {}
   
    #[Route("/absenceReport", name:"register_and_list_absenceReport")]
    public function absenceReport(Request $request): Response
    {
        $mySession = $request->getSession();
        
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
        
        $duty = $this->dutyRepository->findOneBy([
            'duty' => ConstantsClass::SUPERVISOR_DUTY
        ]);
        
        $teachers = $this->teacherRepository->findBy([
            'duty' => $duty,
            'subSystem' => $subSystem,
            'schoolYear' => $schoolYear
        ]);

        return $this->render('register_and_list/absenceReport.html.twig', [
            'teachers' => $teachers,
            'school' => $school,
        ]);
    }

}
