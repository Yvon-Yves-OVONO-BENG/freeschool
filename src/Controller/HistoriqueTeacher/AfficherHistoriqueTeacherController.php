<?php

namespace App\Controller\HistoriqueTeacher;

use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
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
class AfficherHistoriqueTeacherController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected HistoriqueTeacherRepository $historiqueTeacherRepository, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route('/afficher-historique-teacher/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}', name: 'afficher_historique_teacher')]
    public function afficherHistoriqueTeacher(Request $request, int $a = 0, int $m = 0, int $s = 0): Response
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
        }
        else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $historiqueTeachers = $this->historiqueTeacherRepository->findBy(['supprime' => 0 ], ['enregistreLeAt' => 'DESC']);

        return $this->render('historique_teacher/afficherHistoriqueTeacher.html.twig', [
            'historiqueTeachers' => $historiqueTeachers,
            'school' => $school,
        ]);
    }
}
