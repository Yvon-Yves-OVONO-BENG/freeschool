<?php

namespace App\Controller;

use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\StudentRepository;
use App\Service\ImpressionFicheSport;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class FicheSportController extends AbstractController
{

    public function __construct(
        protected StudentRepository $eleveRepository,
        protected SchoolRepository $schoolRepository,
        protected SchoolYearRepository $anneeRepository,
        protected ClassroomRepository $classeRepository, 
        protected ImpressionFicheSport $impressionFicheSport,
    ) {
    }

    #[Route(path: '/fiche-sport', name: 'fiche_sport')]
    public function ficheSport(Request $request): Response
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
        
    
        $classes = $this->classeRepository->findBy([
            'schoolYear' => $schoolYear
        ]);
        if ($request->request->has('imprimer')) 
        {
            
            $maSession = $request->getSession();
            $maSession->set('classe',$request->request->get('classe'));
            $eleves = $this->eleveRepository->findBy(
                [
                    'classroom' => $this->classeRepository->find($request->request->get('classe')),
                    'schoolYear' => $this->anneeRepository->find($schoolYear->getId())
                ],
                [
                    'fullName' => 'ASC'
                ]
            );

            $pdf = $this->impressionFicheSport->impresionFiche($eleves, $this->classeRepository->find($request->request->get('classe')));
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output("Sport sheet of - ".$this->classeRepository->find($request->request->get('classe'))->getClassroom(), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output("Fiche de sport de la classe de  - ".$this->classeRepository->find($request->request->get('classe'))->getClassroom(), "I"), 200, ['content-type' => 'application/pdf']);
            }
            
            
        }

        return $this->render('sport/sportAfficher.html.twig', [
            'classes' => $classes,
            'school' => $school,
        ]); 
    }
}
