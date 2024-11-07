<?php

namespace App\Controller\StudentTrash;

use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class ListStudentTrashController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected StudentRepository $studentRepository, 
        )
    {}

    #[Route('/list-student-trash/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}', name: 'list_student_trash')]
    public function listStudentTrash(Request $request, int $a = 0, int $m = 0,  int $s = 0): Response
    {
        $mySession = $request->getSession();
        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout', null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
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

        ////je récupère tous les élèves supprimés
        $studentTrashs = $this->studentRepository->findBy([
            'supprime' => 1, 
            'schoolYear' => $schoolYear, 
            'subSystem' => $subSystem, 
        ]);

        return $this->render('student_trash/listStudentTrash.html.twig', [
            'studentTrashs' => $studentTrashs,
            'school' => $school,
        ]);
    }
}
