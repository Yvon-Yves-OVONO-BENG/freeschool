<?php

namespace App\Controller\Statistic;

use App\Entity\ConstantsClass;
use App\Service\ClassroomService;
use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class SlipPerClassController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/slipPerClass", name:"statistic_slipPerClass")]
   public function slipPerClass(Request $request): Response
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

        // on recupèere toutes les classes
        if($this->isGranted(ConstantsClass::ROLE_CENSOR))
        {
            /**
             * @var User
             */
            $user = $this->getUser();
            $classrooms = $this->classroomRepository->findCensorClassrooms($user->getTeacher(), $schoolYear, $subSystem);
        }else 
        {
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            
        }
      $terms = $this->termRepository->findBy([], ['term' => 'ASC']);
      $sequences = $this->sequenceRepository->findBy([], ['sequence' => 'ASC']);

      $classrooms = $this->classroomService->splitClassrooms($classrooms);

       return $this->render('statistic/slipPerClass.html.twig', [
           'classrooms' => $classrooms,
           'terms' => $terms,
           'sequences' => $sequences,
           'annualTerm' => ConstantsClass::ANNUEL_TERM,
           'school' => $school,
       ]);
   }

}