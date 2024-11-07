<?php

namespace App\Controller\Subject;

use App\Repository\SubjectRepository;
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

#[Route("/subject")]
class DeleteSubjectController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SubjectRepository $subjectRepository, 
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route("/deleteSubject/{slug}", name:"subject_deleteSubject")]
    public function deleteSubject(Request $request, string $slug): Response
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
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $subject = $this->subjectRepository->findOneBySlug([
            'slug' => $slug
        ]);

        if(count($subject->getLessons()))
        {
            $this->addFlash('info', $this->translator->trans('Deleting denied. This subject is taught in lessons'));
            
            $mySession->set('suppression', 1);

            return $this->redirectToRoute('subject_displaySubject', [ 's' => 1]);
        }else 
        {
            $this->em->remove($subject);
            $this->em->flush();
            
            $this->addFlash('info', $this->translator->trans('Subject deleted successfully'));
            $mySession->set('suppression', 1);

            return $this->redirectToRoute('subject_displaySubject',
            [ 's' => 1]);
        }

        
    }
}
