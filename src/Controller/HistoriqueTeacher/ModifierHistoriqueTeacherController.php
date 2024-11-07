<?php

namespace App\Controller\HistoriqueTeacher;

use App\Service\SchoolYearService;
use App\Form\HistoriqueTeacherType;
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
class ModifierHistoriqueTeacherController extends AbstractController
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

    #[Route('/modifier-historique-teacher/{slug}', name: 'modifier_historique_teacher')]
    public function modifierHistoriqueTeacher(Request $request, string $slug): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $historiqueTeacher = $this->historiqueTeacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $nombreHeureCourante = $historiqueTeacher->getNombreHeure();

        $form = $this->createForm(HistoriqueTeacherType::class, $historiqueTeacher);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $absenceTeacher = $this->absenceTeacherRepository->findOneBy([
                'teacher' => $historiqueTeacher->getTeacher()
            ]);

            $nombreHeureFormulaire  = $historiqueTeacher->getNombreHeure();

            if ($nombreHeureCourante > $nombreHeureFormulaire) 
            {
                $nouveauNombreHeure = $nombreHeureCourante - $nombreHeureFormulaire;
            } 
            else 
            {
                $nouveauNombreHeure = $nombreHeureFormulaire - $nombreHeureCourante;
            }
            
            if ($nombreHeureCourante > $nombreHeureFormulaire) 
            {
                $absenceTeacher->setAbsenceTeacher($absenceTeacher->getAbsenceTeacher() - $nouveauNombreHeure);
                $this->em->persist($absenceTeacher);
            }
            else 
            {
                $absenceTeacher->setAbsenceTeacher($absenceTeacher->getAbsenceTeacher() + $nouveauNombreHeure);
                $this->em->persist($absenceTeacher);
            }

            $this->em->flush(); // On modifie

            $this->addFlash('info', $this->translator->trans('Hours teacher updated successfully'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des classes
            return $this->redirectToRoute('afficher_historique_teacher', [ 'm' => 1]);

        }

        $historiqueTeachers = $this->historiqueTeacherRepository->findBy([], ['enregistreLeAt' => 'ASC']);

        return $this->render('historique_teacher/ajoutHistoriqueTeacher.html.twig', [
            'formHistoriqueTeacher' => $form->createView(),
            'slug' => $slug,
            'historiqueTeachers' => $historiqueTeachers,
            'school' => $school,
        ]);
    }
}
