<?php

namespace App\Controller\Evaluation;

use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/evaluation")]
class UpdateMarkByAdminController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        )
    {}

    #[Route("/updateMarkByAdmin/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}", name:"evaluation_updateMarkByAdmin")]
    public function updateMarkByAdmin(Request $request, int $a = 0, int $m = 0, int $s = 0)
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

        if($request->request->has('updateMarkExtra'))
        {   $slugTeacher = $this->teacherRepository->find($request->request->get('teacher'))->getSlug() ;

            return $this->redirectToRoute('evaluation_markRecorder', [
                'slugTeacher' => $slugTeacher
            ]);
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $teachers = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
        ], [
            'fullName' => 'ASC'
        ]);

        return $this->render('evaluation/updateMarkByAdmin.html.twig', [
            'teachers' => $teachers,
            'school' => $school,
        ]);
    }

}
