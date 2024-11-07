<?php

namespace App\Controller\Deliberation;

use App\Repository\ClassroomRepository;
use App\Repository\StudentRepository;
use App\Service\SchoolYearService;
use App\Service\StudentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route('deliberation')]
class CancelDeliberationController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em, protected ClassroomRepository $classroomRepository, protected TranslatorInterface $translator, protected SchoolYearService $schoolYearService, protected StudentRepository $studentRepository, protected StudentService $studentService)
    {}

    #[Route('/cancel-deliberation/{idC}', name: 'cancel_deliberation')]
    public function cancelDeliberation(Request $request, int $idC): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        // On recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        $classroom = $this->classroomRepository->find($idC);
        
        foreach ($classroom->getStudents() as $student) 
        {
            $studen = $this->studentRepository->findOneBy([
                'fullName' => $student->getFullName(),
                'schoolYear' => $nextSchoolYear,
            ]);
            
            $this->studentService->deleteStudentDeliberationCancel($studen, $classroom);

        }

        $this->addFlash('info', $this->translator->trans('Deliberations canceled successfully !'));

        return $this->redirectToRoute('deliberation_displayDeliberation', [
            'idC' => $idC,
            'notification' => 2,
        ]);
    }
}
