<?php

namespace App\Controller\Grade;

use App\Entity\Grade;
use App\Form\GradeType;
use App\Service\SchoolYearService;
use App\Repository\GradeRepository;
use App\Repository\SchoolRepository;
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

#[Route('/grade')]
class SaveGradeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected GradeRepository $gradeRepository, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route('/saveGrade', name: 'grade_saveGrade')]
    public function saveGrade(Request $request): Response
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
        $verrou = $mySession->get('verrou');
        $slug = 0;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $grade = new Grade();       
        
        $form = $this->createForm(GradeType::class, $grade);

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
            $dernierGrade = $this->gradeRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierGrade) 
            {
                $id = $dernierGrade[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $grade->setGrade(strtoupper($grade->getGrade()))
                    ->setSlug($slug.$id);

            // On ajoute dans la BD
            $this->em->persist($grade);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Rank saved with success !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $mySession->set('ajout', 1);

            #je déclare une nouvelle instance
            $grade = new Grade();

            $form = $this->createForm(GradeType::class, $grade);
            
        }

        $grades = $this->gradeRepository->findBy([], ['grade' => 'ASC']);

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('grade/saveGrade.html.twig', [
            'slug' => $slug,
            'grades' => $grades,
            'formGrade' => $form->createView(),
            'school' => $school,
            ]);
    }

}
