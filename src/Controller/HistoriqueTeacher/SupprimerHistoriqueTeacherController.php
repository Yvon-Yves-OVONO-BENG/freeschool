<?php

namespace App\Controller\HistoriqueTeacher;

use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AbsenceTeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\HistoriqueTeacherRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/historiqueTeacher')]
class SupprimerHistoriqueTeacherController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        protected AbsenceTeacherRepository $absenceTeacherRepository,
        protected HistoriqueTeacherRepository $historiqueTeacherRepository, 
        )
    {}
    
    #[Route('/supprimer-historique-teacher/{slug}', name: 'supprimer_historique_teacher')]
    public function supprimerHistoriqueTeacher(Request $request, string $slug): Response
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
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $historiqueTeacher = $this->historiqueTeacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $absenceTeacher = $this->absenceTeacherRepository->findOneBy([
            'teacher' => $historiqueTeacher->getTeacher()
        ]);

        if ($absenceTeacher) 
        {
            $absenceTeacher->setAbsenceTeacher($absenceTeacher->getAbsenceTeacher() - $historiqueTeacher->getNombreHeure());
            $this->em->persist($absenceTeacher);
            
            if ($absenceTeacher->getAbsenceTeacher() == 0) 
            {
                $this->em->remove($absenceTeacher);
            }
          
            $this->em->persist($absenceTeacher);
        } 

        $this->em->remove($historiqueTeacher);
        $this->em->flush();
        
        $this->addFlash('info', $this->translator->trans('Hours teacher deleted successfully'));

        #j'affecte 1 à ma variable pour afficher le message
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('afficher_historique_teacher',[ 's' => 1 ]);
    }
}
