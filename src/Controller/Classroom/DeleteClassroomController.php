<?php

namespace App\Controller\Classroom;

use App\Repository\ClassroomRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\UnrankedCoefficientRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/classroom")]
class DeleteClassroomController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected ClassroomRepository $classroomRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected UnrankedCoefficientRepository $unrankedCoefficientRepository, 
        )
    {}

    #[Route("/deleteClassroom/{slug}", name:"classroom_deleteClassroom")]
    public function deleteClassroom(Request $request, string $slug): Response
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
        
        // Si la modification des données est verrouillée, on retourne au menu principal
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $classroom = $this->classroomRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $unrankedCoefficient = $this->unrankedCoefficientRepository->findOneByClassroom($classroom);

        if(count($classroom->getStudents()))
        {
            $this->addFlash('info', $this->translator->trans('Impossible to delete a classroom with students'));
            $mySession->set('suppression', 1);
            return $this->redirectToRoute('classroom_displayClassroom', [ 's' => 1]);
        }
        elseif(count($classroom->getLessons()))
        {
            $this->addFlash('info', $this->translator->trans('Impossible to delete a classroom where lessons are scheduled'));
            $mySession->set('suppression', 1);
            return $this->redirectToRoute('classroom_displayClassroom',[ 's' => 1]);
        }
        else 
        {
            $this->em->remove($unrankedCoefficient);
            $this->em->remove($classroom);
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Classroom deleted with success !'));
            $mySession->set('suppression', 1);

            return $this->redirectToRoute('classroom_displayClassroom', [ 's' => 1]);
        }

        
    }

}