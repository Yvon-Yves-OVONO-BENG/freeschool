<?php

namespace App\Controller\Report;

use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RegistrationRepository;
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
class DesactivePrintReportInsolvableController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected StudentRepository $studentRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected VerrouInsolvableRepository $verrouInsolvableRepository, 
        )
    {}

    #[Route('/desactive-print-report-insolvable/{transcript}', name: 'desactive_print_report_insolvable')]
    public function desactivePrintReportInsolvable(Request $request, int $transcript = 0): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        

        ///////je récupère tous les élèves
        $students = $this->studentRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        ///////je récupère l"état du verrou insolvale
        $verrouInsolvable = $this->verrouInsolvableRepository->find(1);

        ///pour chaque élève
        foreach ($students as $student) 
        {
            ///pour chaque élève je récupère ses frais de scolarité
            $studentRegistration = $this->registrationRepository->findOneBy(['student' => $student]);

            $sumFees = 0;

            ////si les frais existent je fais la somme des frais
            if ($studentRegistration) 
            {
                $sumFees =  (int)$studentRegistration->getApeeFees() + (int)$studentRegistration->getComputerFees() + (int)$studentRegistration->getCleanSchoolFees() + (int)$studentRegistration->getMedicalBookletFees() + (int)$studentRegistration->getPhotoFees();

            } 
            /////sinon je mets la somme des frais à 0
            else 
            {
                $sumFees = 0;
            }
            
            ////c'est pour activer l'impression des bulletins des insolvables
            ////si la somme des frais est égale à 25 000 ou 30 000 j'active l'impression de son bulletin
            if ($sumFees == 25000 || $sumFees == 30000) 
            {
                $student->setSolvable(1);
            }
            //////sinon je désactive l'impression de son bulletin
            else
            {
                $student->setSolvable(0);
            }

            $this->em->persist($student);
        }

        $verrouInsolvable->setVerrouInsolvable(0);
        
        $this->em->persist($verrouInsolvable);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Print report of insolvable desactivate with success !'));
        $mySession->set('miseAjour', 1);

        if ($transcript == 1) 
        {
            return $this->redirectToRoute('transcript_student');
        } 
        else 
        {
            return $this->redirectToRoute('report_report');
        }
    }
}
