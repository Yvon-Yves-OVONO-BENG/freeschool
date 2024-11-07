<?php

namespace App\Controller\Duty;

use App\Entity\Duty;
use App\Form\DutyType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DutyRepository;
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

#[Route("/duty")]
class SaveDutyController extends AbstractController
{
    public function __construct(protected DutyRepository $dutyRepository, protected EntityManagerInterface $em, protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator, protected SchoolRepository $schoolRepository)
    {
    }

    #[Route("/saveDuty", name:"duty_saveDuty")]
    public function saveDuty(Request $request): Response
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

        $slug = 0;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $duty = new Duty();       
        
        $form = $this->createForm(DutyType::class, $duty);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait la derniere matiere de la table
            $dernierDuty =  $this->dutyRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDuty) 
            {
                $id = $dernierDuty[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $duty->setDuty(strtoupper($duty->getDuty()))
            // ->setSlug($slug.$id)
            ;

            $this->em->persist($duty);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Duty saved successfully'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $mySession->set('ajout', 1);

            $duty = new Duty();
            $form = $this->createForm(DutyType::class, $duty);
            
        }

        $dutys = $this->dutyRepository->findBy([], ['duty' => 'ASC']);

        return $this->render('duty/saveDuty.html.twig', [
            'slug' => $slug,
            'dutys' => $dutys,
            'formDuty' => $form->createView(),
            'school' => $school,
            ]);
    }

}
