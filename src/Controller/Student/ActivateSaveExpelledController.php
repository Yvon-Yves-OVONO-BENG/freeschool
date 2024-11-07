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
class ActivateSaveExpelledController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em, protected VerrouInsolvableRepository $verrouInsolvableRepository, protected TranslatorInterface $translator)
    {}
    
    #[Route('/activate-save-expelled', name: 'activate_save_expelled')]
    public function activeSaveExpelled(Request $request): Response
    {
        $mySession = $request->getSession();
        
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        
        
        ///////je récupère l"état du verrou insolvale
        $verrouInsolvable = $this->verrouInsolvableRepository->find(1);

        $this->em->persist($verrouInsolvable);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Registration of excluded successfully activate !'));

        $mySession->set('ajout', 1);

        return $this->redirectToRoute('student_saveStudent', []);
    }
}
