<?php

namespace App\Controller\StudentTrash;

use App\Service\SchoolYearService;
use App\Service\StudentTrashService;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class DeleteAllStudentTrashController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,  
        protected SchoolYearService $schoolYearService, 
        protected StudentTrashService $studentTrashService,
        protected StudentRepository $studentRepository, 
        )
    {}

    #[Route('/delete-all-student-trash', name: 'delete_all_student_trash')]
    public function deleteStudentTrash(Request $request): Response
    {
        $mySession = $request->getSession();

        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);


        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');

        $user = $this->getUser();
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $studentTrashs = $this->studentRepository->findBy([
        ]);
        
        
        $this->studentTrashService->deleteAllStudentTrash($studentTrashs);
            
        $this->addFlash('info', $this->translator->trans('Student deleted with success !'));
        
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('list_student_trash', [
            's' => 1,
        ]);
    }
}
