<?php

namespace App\Controller\Report;

use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\VerrouInsolvableRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class ActivatePrintReportInsolvableController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em, protected StudentRepository $studentRepository, protected VerrouInsolvableRepository $verrouInsolvableRepository, protected TranslatorInterface $translator)
    {
        
    }
    #[Route('/activate-print-report-insolvable', name: 'activate_print_report_insolvable')]
    public function activatePrintReportInsolvable(Request $request): Response
    {
        ////je recupère tpute ma session
        $mySession = $request->getSession();

        //////je récupère l'année scolaire
        $schoolYear = $mySession->get('schoolYear');

        ///////je récupère tous les élèves de l'année scolaire
        $students = $this->studentRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        ///////je récupère l"état du verrou insolvale
        $verrouInsolvable = $this->verrouInsolvableRepository->find(1);

        foreach ($students as $student) 
        {
            $student->setSolvable(1);

            $this->em->persist($student);
        }

        $verrouInsolvable->setVerrouInsolvable(1);

        $this->em->persist($verrouInsolvable);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Print report of insolvable activate successfully !'));
        return $this->redirectToRoute('report_report', [
            'notification' => 1
        ]);
    }
}
