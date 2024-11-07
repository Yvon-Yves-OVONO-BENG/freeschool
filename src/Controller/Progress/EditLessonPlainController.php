<?php

namespace App\Controller\Progress;

use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Form\ProgressLessonPrevuType;
use App\Repository\SchoolYearRepository;
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

#[Route("/progress")]
class EditLessonPlainController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/edit-lesson-plain/{slug}", name:"edit_lesson_plain")]
    public function editLessonPlain(Request $request, string $slug = ""): Response
    {
        $mySession = $request->getSession();
        
        #mes variables témoin pour afficher les sweetAlert
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

        /**
         * @var User
         */
        $user = $this->getUser();
        $teacher = $user->getTeacher();

        // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $progress = $this->lessonRepository->findOneBySlug(['slug' => $slug]) ;
        
        $form = $this->createForm(ProgressLessonPrevuType::class, $progress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $this->em->persist($progress);
            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Number of planned lessons successfully updated'));
            $mySession->set('miseAjour', 1);
            // On se redirige sur la page d'affichage des leçons prévues
            return $this->redirectToRoute('display_lesson_plain', ['m' => 1 ]);
            
        }

        return $this->render('progress/addLessonPlain.html.twig', [
            'formProgressLessonPrevue' => $form->createView(),
            'school' => $school,
        ]);
    }


}
