<?php

namespace App\Controller;

use App\Entity\ConstantsClass;
use App\Repository\SubSystemRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class ChangerSousSystemController extends AbstractController
{
    public function __construct( 
        protected SubSystemRepository $subSystemRepository,
        protected TranslatorInterface $translator,
        )
    {}

    #[Route("/changer-sous-system", name:"changer_sous_system")]
    public function changerSousSystem(Request $request): Response
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

        if ($subSystem->getSubsystem()== ConstantsClass::FRANCOPHONE) 
        {
            $subSystem = $this->subSystemRepository->findOneBy(['subSystem' => ConstantsClass::ANGLOPHONE]);
            $mySession->set('subSystem', $subSystem);
        }
        elseif ($subSystem->getSubsystem() == ConstantsClass::ANGLOPHONE) 
        {
            $subSystem = $this->subSystemRepository->findOneBy(['subSystem' => ConstantsClass::FRANCOPHONE]);
            $mySession->set('subSystem', $subSystem);
        } 
       
        $this->addFlash('info', $this->translator->trans('Subsystem changed successfully !'));
        
        $mySession->set('ajout', 1);

        return $this->redirectToRoute('home_dashboard',
            ['a' => 1 ]
        );

    }
}
