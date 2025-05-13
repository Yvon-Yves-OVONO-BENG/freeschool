<?php

namespace App\Controller\Diploma;

use App\Service\SchoolYearService;
use App\Repository\DiplomaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/diploma")]
class DeleteDiplomaController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected DiplomaRepository $diplomaRepository, 
        )
    {}

    #[Route("/deletediploma/{slug}", name:"diploma_deleteDiploma")]
    public function deleteDiploma(Request $request, string $slug): Response
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

        $diploma = $this->diplomaRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $this->em->remove($diploma);
        $this->em->flush();
            
        $this->addFlash('info', $this->translator->trans('Diploma deleted with success !'));
        
        #j'affecte 1 à ma variable pour afficher le message
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('diploma_displayDiploma', [ 's' => 1]);
        
    }
}
