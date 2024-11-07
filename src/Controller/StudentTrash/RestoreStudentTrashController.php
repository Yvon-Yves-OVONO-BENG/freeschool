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
class RestoreStudentTrashController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,  
        protected SchoolYearService $schoolYearService, 
        protected StudentTrashService $studentTrashService,
        protected StudentRepository $studentRepository, 
        )
    {}

    #[Route('/restore-student-trash/{slug}', name: 'restore_student_trash')]
    public function restoreStudentTrash(Request $request, string $slug): Response
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

        $studentTrash = $this->studentRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        // $this->studentTrashService->restoreStudentTrash($request, $studentTrash, $user);
        $studentTrash->setSupprime(0);
        $this->em->persist($studentTrash);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Student restored successfully'));
        
        $mySession->set('miseAjour', 1);

        return $this->redirectToRoute('list_student_trash', [
            'm' => 1,
        ]);
    }
}
