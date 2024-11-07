<?php

namespace App\Controller\Deliberation;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Entity\DeliberationElements\DeliberationRow;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\DecisionRepository;
use App\Repository\SchoolRepository;
use App\Service\ClassroomService;
use App\Service\DeliberationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class DisplayDeliberationController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository,  protected DeliberationService $deliberationService, protected DecisionRepository $decisionRepository, protected EntityManagerInterface $em, protected ClassroomService $classroomService, protected SchoolRepository $schoolRepository )
    {
    }

    /**
     * @Route("/displayDeliberation/{idC<[0-9]+>}/{idS<[0-9]+>}/{notification}", name="deliberation_displayDeliberation")
     */
    public function displayDeliberation(Request $request, int $idC = 0, int $idS = 0, int $notification = 0): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        if(!$request->isMethod('POST')) 
        {
            // On transfère l'établissement au next year (s'il n'y est pas encore)
            $this->deliberationService->transferSchool($request);

            // On créé le verrou de la nouvelle année (s'il n'y est pas encore)
            $this->deliberationService->createVerrou();

            // on créé les verrous des trimestres
            $this->deliberationService->createVerrouReport();

            // on créé les verrous des séquences
            $this->deliberationService->createVerrouSequence();

            // On transfer les fees à la nouvelle année
            $this->deliberationService->TransferFees($request);

            // On transfère les départements au next year (ceux qui n'y sont pas encore)
            $this->deliberationService->transferDepartments($request);

            // On transfère les matières au next year (celles qui n'y sont pas encore)
            $this->deliberationService->transferSubjects($request);

            // On transfère les enseignants au next year (ceux qui n'y sont pas encore)
            $this->deliberationService->transferTeachers($request);
            
            // On transfère les classes au next year (celles qui n'y sont pas encore)
            // et on tranfère les limites des coefficients par classe
            $this->deliberationService->transferClassrooms($request);

            //On met à jour les AP des départements
            $this->deliberationService->setEducationalFacilitator($request);
            
            // on met à jour le headmaster dans school
            $this->deliberationService->setSchoolHeadmaster($request);

        }
        
        $selectedClassroom = new Classroom();
        $deliberation = new DeliberationRow();
        $decisions = [];
        $deliberations = [];
        $nextClassrooms = [];
        $numberOfStudents = 0;
        $isUpdate = false;

        $girls = 0;
        $boys = 0;

        if($idC)
        {
            $request->request->set('classroom', $idC);
        }

        if($request->request->has('classroom'))
        {   
            // On concerve la classe sélectionnée
            $selectedClassroom = $this->classroomRepository->find($request->request->get('classroom'));

            // On recupère les classes de niveaux immédiatement superieurs pour admis
            $nextClassrooms = $this->deliberationService->getNextClassrooms($selectedClassroom, $subSystem);

            $numberOfStudents = count($selectedClassroom->getStudents());

            foreach ($selectedClassroom->getStudents() as $sudent) 
            {
                if ($sudent->getSex()->getSex() == "M") 
                {
                    $boys = $boys + 1;

                }elseif ($sudent->getSex()->getSex() == "F") 
                {
                    $girls = $girls + 1;
                }
            }
            // on recupère les décisions à afficher
            $decisions = $this->decisionRepository->findDecisionToDisplay($selectedClassroom);
            
            if($idS)
            {
                $deliberation = $this->deliberationService->getStudentDeliberation($idS);
                $isUpdate = true;
            }else
            {
                // On recupère les délibérations à afficher
                $deliberations = $this->deliberationService->getDeliberations($selectedClassroom, $subSystem);

            }
        }
        // on recupère les classes à afficher
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
        
        $classrooms = $this->classroomService->splitClassrooms($classrooms);

        //mes variables desnotifications
        $notificationDeliberation = false;

        if ($notification == 1) 
        {
            $notificationDeliberation = true;
        } 
        
        return $this->render('deliberation/displayDeliberation.html.twig', [
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'notificationDeliberation' => $notificationDeliberation,
            'decisions' => $decisions,
            'deliberations' => $deliberations,
            'deliberation' => $deliberation,
            'boys' => $boys,
            'girls' => $girls,
            'effectif' => $numberOfStudents,
            'nextClassrooms' => $nextClassrooms,
            'unrankedAverage' => ConstantsClass::UNRANKED_AVERAGE,
            'exclu' => ConstantsClass::DECISION_EXPELLED,
            'admis' => ConstantsClass::DECISION_PASSED,
            'demissionnaire' => ConstantsClass::DECISION_RESIGNED,
            'termine' => ConstantsClass::DECISION_FINISHED,
            'excluSiEchec' => ConstantsClass::DECISION_EXPELLED_IF_FAILED,
            'redoubleSiEchec' => ConstantsClass::DECISION_REAPETED_IF_FAILED,
            'isUpdate' => $isUpdate,
            'school' => $school,
        ]);
    }

}