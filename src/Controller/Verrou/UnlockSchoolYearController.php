<?php

namespace App\Controller\Verrou;

use App\Repository\VerrouRepository;
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

#[Route("/verrou")]
class UnlockSchoolYearController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected VerrouRepository $verrouRepository, 
        )
    {}
    
    #[Route("/unlockSchoolYear", name:"verrou_unlockSchoolYear")]
    public function unlockSchoolYear(Request $request): Response
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
        
        // On recupère le verrou avec le repository pour qu'il soit suivi par le entity manager
        $verrou = $this->verrouRepository->findOneBySchoolYear($schoolYear);

        // on déverrouille l'année
        $verrou->setVerrou(0);
        $this->em->flush();

        // On modifie le verrou dana la session
        $mySession->set('verrou', $verrou);
        
        $this->addFlash('info', $this->translator->trans('All changes unlocked successfully'));
        $mySession->set('miseAjour', 1);
        return $this->redirectToRoute('verrou_displayLockOption', ['m' => 1]);
    }

}
