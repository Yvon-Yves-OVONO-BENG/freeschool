<?php

namespace App\Controller\Student;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\VerrouInsolvableRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/student')]
class DesactiveSaveExpelledController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected VerrouInsolvableRepository $verrouInsolvableRepository, 
        )
    {}

    #[Route('/desactive-save-expelled', name: 'desactive_save_expelled')]
    public function desactivate(Request $request): Response
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
        
        ///////je récupère l"état du verrou des exclus
        $verrouInsolvable = $this->verrouInsolvableRepository->find(1);
        
        $this->em->persist($verrouInsolvable);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Exclusion registration successfully disabled!'));
        $mySession->set('miseAjour', 1);

        return $this->redirectToRoute('student_saveStudent', [
            'm' => 1
        ]);
    }
}
