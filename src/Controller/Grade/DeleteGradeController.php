<?php

namespace App\Controller\Grade;

use App\Repository\GradeRepository;
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

#[Route('/grade')]
class DeleteGradeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected GradeRepository $gradeRepository, 
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route('/deleteGrade/{slug}', name: 'grade_deleteGrade')]
    public function deleteGrade(Request $request, string $slug): Response
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

        $grade = $this->gradeRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $this->em->remove($grade);
        $this->em->flush();
        
        $this->addFlash('info', $this->translator->trans('Rank deleted with success !'));

        #j'affecte 1 à ma variable pour afficher le message
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('grade_displayGrade',[ 's' => 1 ]);
        
    }
}
