<?php

namespace App\Controller\HistoriqueTeacher;

use DateTime;
use App\Entity\AbsenceTeacher;
use App\Entity\HistoriqueTeacher;
use App\Service\SchoolYearService;
use App\Form\HistoriqueTeacherType;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AbsenceTeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\HistoriqueTeacherRepository;
use App\Repository\SubSystemRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/historiqueTeacher')]
class AjoutHistoriqueTeacherController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        protected SequenceRepository $sequenceRepository,
        protected SubSystemRepository $subSystemRepository,
        protected AbsenceTeacherRepository $absenceTeacherRepository,
        protected HistoriqueTeacherRepository $historiqueTeacherRepository, 
        )
    {}
    
    #[Route('/ajout-historique-teacher', name: 'ajout_historique_teacher')]
    public function ajoutHistoriqueTeacher(Request $request): Response
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

        $subSystem = $this->subSystemRepository->find($subSystem->getId());

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        $verrou = $mySession->get('verrou');
        $slug = 0;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $historiqueTeacher = new HistoriqueTeacher();       
        
        $form = $this->createForm(HistoriqueTeacherType::class, $historiqueTeacher);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
            $slugAbsence      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            for($i=0;$i < 15;$i++) 
            { 
                $slugAbsence .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait le dernier enegistrement de la table
            $dernierHistoriqueTeacher = $this->historiqueTeacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            if ($dernierHistoriqueTeacher) 
            {
                $id = $dernierHistoriqueTeacher[0]->getId();
            } 
            else 
            {
                $id = 1;
            }
            
            $absenceTeacher = $this->absenceTeacherRepository->findOneBy([
                'teacher' => $form->getData()->getTeacher()
            ]);

            if ($absenceTeacher && $absenceTeacher->isSupprime() == 0 && 
                $absenceTeacher->getTerm()->getTerm() == $form->getData()->getSequence()->getTerm()->getTerm()) 
            {
                $absenceTeacher->setAbsenceTeacher($absenceTeacher->getAbsenceTeacher() + $form->getData()->getNombreHeure());
                $this->em->persist($absenceTeacher);
            } 
            else 
            {
                $absenceTeacher = new AbsenceTeacher;
                $absenceTeacher->setTeacher($form->getData()->getTeacher())
                ->setTerm($form->getData()->getSequence()->getTerm())
                ->setCreatedBy($this->getUser())
                ->setUpdatedBy($this->getUser())
                ->setAbsenceTeacher($form->getData()->getNombreHeure())
                ->setCreatedAt(new DateTime('now'))
                ->setUpdatedAt(new DateTime('now'))
                ->setSlug($slugAbsence.$id)
                ;

                $this->em->persist($absenceTeacher);
            }
            
            $historiqueTeacher->setSlug($slug.$id)
                ->setEnregistreLeAt(new DateTime('now'))
                ->setSubSystem($subSystem)
                ->setEnregistrePar($this->getUser())
                ->setSupprime(0)
                ;

            // On ajoute dans la BD
            $this->em->persist($historiqueTeacher);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Absence teacher saved with success !'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $mySession->set('ajout', 1);

            #je déclare une nouvelle instance
            $historiqueTeacher = new HistoriqueTeacher();

            $form = $this->createForm(HistoriqueTeacherType::class, $historiqueTeacher);
            
        }

        $historiqueTeachers = $this->historiqueTeacherRepository->findBy(['supprime' => 0 ], ['enregistreLeAt' => 'DESC']);

        #je rénitialise mon slug
        $slug = 0;

        return $this->render('historique_teacher/ajoutHistoriqueTeacher.html.twig', [
            'slug' => $slug,
            'historiqueTeachers' => $historiqueTeachers,
            'formHistoriqueTeacher' => $form->createView(),
            'school' => $school,
        ]);
    }
}
