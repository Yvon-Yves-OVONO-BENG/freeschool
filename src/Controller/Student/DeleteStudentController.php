<?php

namespace App\Controller\Student;

use App\Service\StudentService;
use App\Service\SchoolYearService;
use App\Repository\StudentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class DeleteStudentController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected StudentService $studentService,
        protected TranslatorInterface $translator,  
        protected StudentRepository $studentRepository, 
        protected SchoolYearService $schoolYearService,
        )
    {}

    #[Route("/deleteStudent/{slug}/{trash}", name:"student_deleteStudent")]
    public function deleteStudent(Request $request, string $slug, int $trash = 0 ): Response
    {
        $mySession = $request->getSession();
        
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        

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

        $student = $this->studentRepository->findOneBySlug([
            'slug' => $slug 
        ]);
        
        $idC = $student->getClassroom()->getId();
        
        // $this->studentService->deleteStudent($student, $user);
        $student->setSupprime(1)
        ->setDeletedAt(new DateTime('now'))
        ->setDeletedBy($this->getUser());

        $this->em->persist($student);
        $this->em->flush();
            
        $this->addFlash('info', $this->translator->trans('Student deleted with success !'));
        
        $mySession->set('suppression', 1);

        if ($trash == 1 ) 
        {
            return $this->redirectToRoute('list_student_trash', ['s' => 1]);
        } 
        else 
        {
            return $this->redirectToRoute('student_displayStudent', ['id' => $idC, 's' => 1 ]);
        }
        
    }
}