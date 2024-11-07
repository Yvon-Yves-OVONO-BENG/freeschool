<?php

namespace App\Controller\Home;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\VerrouRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainMenuController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,  
        protected VerrouRepository $verrouRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route('/home-mainMenu', name: 'home_mainMenu')]
    public function mainMenu(Request $request): Response
    {
        #je récupère ma session
        $mySession = $request->getSession();

        #je récupère l'école de l'année
        $school = $mySession->get('school');
        
        if($request->request->has('schoolYear') && $request->request->has('subSystem'))
        {
            #je récupère ma session
            $mySession = $request->getSession();

            #je récupère l'année scolaire choisi
            $schoolYear = $this->schoolYearRepository->find($request->request->get('schoolYear'));

            #je récupère le sous système choisi
            $subSystem = $this->subSystemRepository->find($request->request->get('subSystem'));
            // dd($subSystem);
            #je récupère l'école de l'année
            $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

            #je récupère le verrou de l'année
            $verrou = $this->verrouRepository->findOneBySchoolYear($schoolYear);

            #je set ma sassion
            $mySession->set('school', $school);
            $school = $mySession->get('school');
            
            $mySession->set('verrou', $verrou);
            $mySession->set('subSystem', $subSystem);
            $mySession->set('schoolYear', $schoolYear);

            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            
        }

        #mon rendu twig
        return $this->render('home/mainMenu.html.twig', [
            'home' => true,
            'school' => $school,
            'teacherDuty' => ConstantsClass::TEACHER_DUTY,
            'supervisorDuty' => ConstantsClass::SUPERVISOR_DUTY,
            'censorDuty' => ConstantsClass::CENSOR_DUTY,
            'councellorDuty' => ConstantsClass::COUNSELLOR_DUTY,
            'treasurerDuty' => ConstantsClass::TREASURER_DUTY,
            'headmasterDuty' => ConstantsClass::HEADMASTER_DUTY,
            'secretaryDuty' => ConstantsClass::SECRETARY_DUTY,
            'apDuty' => ConstantsClass::AP_DUTY
        ]);
    }

}
