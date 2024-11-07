<?php

namespace App\Controller\Classroom;

use App\Entity\Classroom;
use App\Form\ClassroomType;
use App\Entity\ConstantsClass;
use App\Service\SchoolYearService;
use App\Entity\UnrankedCoefficient;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SubSystemRepository;
use App\Repository\SchoolYearRepository;
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
/**
 * @Route("/classroom")
 */
class SaveClassroomController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository, protected EntityManagerInterface $em, protected SchoolYearRepository $schoolYearRepository, protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator, protected SchoolRepository $schoolRepository, protected SubSystemRepository $subSystemRepository)
    {
    }

    /**
     * @Route("/saveClassroom", name="classroom_saveClassroom")
     */
    public function saveClassroom(Request $request, int $forNextYear = 0, int $id = 0): Response
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
        $subSyste = $this->subSystemRepository->find($subSystem->getId());
        $forFirstGroup = false;
        $forMark = true;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }
        
        // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $classroom = new Classroom();       
        
        $form = $this->createForm(ClassroomType::class, $classroom);

        $form->handleRequest($request);

        $slug = 0;
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
            $classroom->setIsDeliberated(false)
                ->setCreatedBy($this->getUser())
                ->setUpdatedBy($this->getUser())
                ->setSchoolYear($schoolYear)
                ->setSubSystem($subSyste)
                ->setSlug($slug.$id)
                ;

            // On ajoute dans la BD
            $this->em->persist($classroom);
            $this->em->flush();

            // On set le coefficient/note limite par defaut
            $unrankedCoefficient = new UnrankedCoefficient();
            $unrankedCoefficient->setClassroom($classroom)
                ->setUnrankedCoefficient(ConstantsClass::UNRANKED_COEFFICIENT)
                ->setForFirstGroup($forFirstGroup)
                ->setForMark($forMark);
                
            $this->em->persist($unrankedCoefficient);
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Classroom saved successfully'));
            $mySession->set('ajout',1);
            
            $classroom = new Classroom();
            $form = $this->createForm(ClassroomType::class, $classroom);
        
        }

        $slug = 0;
        $classrooms = $this->classroomRepository->findAllToDisplay($schoolYear, $subSystem);
        return $this->render('classroom/saveClassroom.html.twig', [
            'formClassroom' => $form->createView(),
            'forNextYear' => $forNextYear,
            'classrooms' => $classrooms,
            'slug' => $slug,
            'school' => $school,
            ]);
    }

}