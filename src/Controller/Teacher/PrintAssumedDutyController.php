<?php

namespace App\Controller\Teacher;

use App\Service\TeacherService;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/teacher")]
class PrintAssumedDutyController extends AbstractController
{
    public function __construct(
        protected TeacherService $teacherService, 
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository,  
        )
    {}

    #[Route("/printAssumedDuty/{slug}/{asd<[0-1]{1}>}/{pe<[0-1]{1}>}", name:"teacher_printAssumedDuty")]
    public function printAssumedDuty(Request $request, string $slug = "", int $asd = 0, int $pe = 0): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $teachers = [];

        if($slug == "" && $asd == 0 && $pe == 0)
        {
            // l'impression vient du proviseur
            $teacherId = $request->request->get('teacher');
            
            if ($teacherId != 0) 
            {
                $teachers[] = $this->teacherRepository->find($teacherId);
            }else
            {
                $teachers = $this->teacherRepository->findAllToDisplay($schoolYear, $subSystem );
            }

            if($request->request->has('pe'))
            {
                // impression des présences effectives
                $pe = 1;
                $asd = 0;

            }elseif($request->request->has('asd'))
            {
                $asd = 1;
                $pe = 0;
                // impression des prises/reprises de service
            }else
            {
                return $this->redirectToRoute('app_logout');
            }

        }else
        {
            // l'impression vient de l'enseignant
            $teachers[] = $this->teacherRepository->findOneBySlug([
                'slug' => $slug
            ]);
        }

        $pdf = $this->teacherService->printAssumedDuty($school, $schoolYear, $teachers, $asd, $pe);

        if($slug == 0 && $asd == 0 && $pe == 0)
        {
            if ($teacherId != 0) 
            {
                $teachers[] = $this->teacherRepository->find($teacherId);

                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Assumed Duty of ".$teachers[0]->getFullName() ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("Présence effective de ".$teachers[0]->getFullName() ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
            }else
            {
                $teachers = $this->teacherRepository->findAllToDisplay($schoolYear, $subSystem );

                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Assumed Duty of all teachers"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("Présence effectives ce tous les enseignants"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
            }
        }
        else
        {
            if ($pe == 1) 
            {
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Attestation of effective presence"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("Présence effective"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
            } 
            elseif($asd == 1)
            {
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Certificate of assumption - resumption"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("Prise - reprise de service"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
            }
        }

    }

}