<?php

namespace App\Controller\Classroom;

use App\Entity\ConstantsClass;
use App\Entity\UnrankedCoefficient;
use App\Form\ClassroomType;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\UnrankedCoefficientRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/classroom")]
class EditClassroomController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        protected ClassroomRepository $classroomRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected UnrankedCoefficientRepository $unrankedCoefficientRepository, 
        )
    {}

    #[Route("/editClassroom/{slug}", name:"classroom_editClassroom")]
    public function editClassroom(Request $request, string $slug, int $forNextYear = 0): Response
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
        $subSyste = $this->subSystemRepository->find($subSystem->getId());
        $verrou = $mySession->get('verrou');

        $forFirstGroup = false;
        $forMark = true;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }
        
        // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        
        $classroom = $this->classroomRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(ClassroomType::class, $classroom);

        // on set le schoolYear pour qu'il soit pris en compte dans la validation du formulaire
        $classroom->setSchoolYear($schoolYear); 

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
            $dernierClassroom = $this->classroomRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierClassroom) 
            {
                $id = $dernierClassroom[0]->getId();
            } 
            else 
            {
                $id = 1;
            }
            // if($classroom->getLevel()->getLevel() > 4)
            // {
            //     $forFirstGroup = true;
            // }
            
            // On set les champs qui ne sont pas pris en compte par le formulaire
            $classroom->setUpdatedBy($this->getUser())
                        ->setSubSystem($subSyste)
                        ->setSlug($slug.$id);
            
            $this->em->persist($classroom);
            $this->em->flush(); // On modifie

            // On Insère le coefficient/note limite par defaut si ça n'existe pas encore
            $unrankedCoefficient = $this->unrankedCoefficientRepository->findOneByClassroom($classroom);
            if(is_null($unrankedCoefficient))
            {
                $unrankedCoefficient = new UnrankedCoefficient();
                $unrankedCoefficient->setClassroom($classroom)
                    ->setUnrankedCoefficient(ConstantsClass::UNRANKED_COEFFICIENT)
                    ->setForFirstGroup($forFirstGroup)
                    ->setForMark($forMark);

                $this->em->persist($unrankedCoefficient);
                $this->em->flush();
            }

            $this->addFlash('info', $this->translator->trans('Classroom updated with success !'));
            
            $mySession->set('miseAjour', 1);
            // On se redirige sur la page d'affichage des classes
            return $this->redirectToRoute('classroom_displayClassroom',
            [ 'm' => 1]);
        }
            
        $classrooms = $this->classroomRepository->findAllToDisplay($schoolYear, $subSystem);

        return $this->render('classroom/saveClassroom.html.twig', [
            'formClassroom' => $form->createView(),
            'slug' => $slug,
            'forNextYear' => $forNextYear,
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'school' => $school
            ]);
    }

}