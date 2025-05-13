<?php

namespace App\Controller\Subject;

use App\Form\SubjectType;
use App\Service\StrService;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
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

#[Route("/subject")]
class EditSubjectController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SubjectRepository $subjectRepository, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/editSubject/{slug}", name:"subject_editSubject")]
    public function saveSubject(Request $request, string $slug): Response
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
        
        $subSyste = $this->subSystemRepository->find($subSystem->getId());
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }
        
        // on ecupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $subject = $this->subjectRepository->findOneBySlug([
            'slug' => $slug
        
        ]);

        $form = $this->createForm(SubjectType::class, $subject);

        // on set le schoolYear pour qu'il soit pris en compte dans la validation du formulaire
        $subject->setSchoolYear($schoolYear); 

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
            $dernierSubject = $this->subjectRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierSubject) 
            {
                $id = $dernierSubject[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            // on met le subject en majuscule
            $subject->setSubject($this->strService->strToUpper($subject->getSubject()))
            ->setSlug($slug.$id)
            ->setSubSystem($subSyste);

            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Subject updated with success !'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des matières
            return $this->redirectToRoute('subject_displaySubject',
            [ 'm' => 1]);
            
        }

        $subjects = $this->subjectRepository->findToDisplay($schoolYear, $subSystem);

        return $this->render('subject/saveSubject.html.twig', [
            'formSubject' => $form->createView(),
            'slug' => $slug,
            'subjects' => $subjects,
            'school' => $school,
            ]);
    }

}
