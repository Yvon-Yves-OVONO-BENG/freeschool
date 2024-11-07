<?php

namespace App\Controller\Grade;

use App\Form\GradeType;
use App\Service\SchoolYearService;
use App\Repository\GradeRepository;
use App\Repository\SchoolRepository;
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
#[Route('/grade')]
class EditGradeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected GradeRepository $gradeRepository, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route('/editGrade/{slug}', name: 'grade_editGrade')]
    public function saveGrade(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

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

        $grade = $this->gradeRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(GradeType::class, $grade);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $grade->setGrade(strtoupper($grade->getGrade()));

            $this->em->flush(); // On modifie

            $this->addFlash('info', $this->translator->trans('Rank updated successfully'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des classes
            return $this->redirectToRoute('grade_displayGrade',
            [ 'm' => 1]);

        }

        $grades = $this->gradeRepository->findBy([], ['grade' => 'ASC']);
        return $this->render('grade/saveGrade.html.twig', [
            'formGrade' => $form->createView(),
            'slug' => $slug,
            'grades' => $grades,
            'school' => $school,
            ]);
    }

}
