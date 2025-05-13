<?php

namespace App\Controller\Diploma;

use App\Entity\Diploma;
use App\Form\DiplomaType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DiplomaRepository;
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

#[Route("/diploma")]
class SaveDiplomaController extends AbstractController
{
    public function __construct(protected DiplomaRepository $diplomaRepository, protected EntityManagerInterface $em, protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator, protected SchoolRepository $schoolRepository)
    {
    }

    #[Route("/saveDiploma", name:"diploma_saveDiploma")]
    public function saveDiploma(Request $request): Response
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

        $diploma = new Diploma();       
        
        $form = $this->createForm(DiplomaType::class, $diploma);

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
            $dernierDiploma =  $this->diplomaRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDiploma) 
            {
                $id = $dernierDiploma[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $diploma->setDiploma(strtoupper($diploma->getDiploma()))
            ->setSlug($slug.$id);

            $this->em->persist($diploma);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Diploma saved with success !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $mySession->set('ajout', 1);

            $diploma = new Diploma();
            $form = $this->createForm(DiplomaType::class, $diploma);
            
        }

        $diplomas = $this->diplomaRepository->findBy([], ['diploma' => 'ASC']);

        return $this->render('diploma/saveDiploma.html.twig', [
            'slug' => $slug,
            'diplomas' => $diplomas,
            'formDiploma' => $form->createView(),
            'school' => $school,
            ]);
    }

}
