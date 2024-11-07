<?php

namespace App\Controller\Report;

use App\Entity\ConstantsClass;
use App\Entity\UnrankedCoefficient;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UnrankedCoefficientRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class EditUnrankedCoefficientController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected UnrankedCoefficientRepository $unrankedCoefficientRepository, 
        )
    {}

    #[Route("/editUnrankedCoefficient/{id<[0-9]+>}/{level<[0-7]{1}>}", name:"report_editUnrankedCoefficient")]
    public function editUnrankedCoefficient(Request $request, int $id = 0, int $level = 0)
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

        $selectedUnrankedCoefficient = new UnrankedCoefficient;

        $levelName = '';
        $coefficient = 0;

        if($request->request->has('unrankedCoefficientToUpdate'))
        {
            if($level != 0)
            {
                switch ($level) 
                {
                    case 1:
                        $levelName = ConstantsClass::LEVEL_1;
                        $coefficient = $request->request->get('level1');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(1, $schoolYear)[0];
                        break;
                    case 2:
                        $levelName = ConstantsClass::LEVEL_2;
                        $coefficient = $request->request->get('level2');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(2, $schoolYear)[0];
                        break;
                    case 3:
                        $levelName = ConstantsClass::LEVEL_3;
                        $coefficient = $request->request->get('level3');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(3, $schoolYear)[0];
                        break;
                    case 4:
                        $levelName = ConstantsClass::LEVEL_4;
                        $coefficient = $request->request->get('level4');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(4, $schoolYear)[0];
                        break;
                    case 5:
                        $levelName = ConstantsClass::LEVEL_5;
                        $coefficient = $request->request->get('level5');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(5, $schoolYear)[0];
                        break;
                    case 6:
                        $levelName = ConstantsClass::LEVEL_6;
                        $coefficient = $request->request->get('level6');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(6, $schoolYear)[0];
                        break;
                    case 7:
                        $levelName = ConstantsClass::LEVEL_7;
                        $coefficient = $request->request->get('level7');
                        $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->findForClassroomsLevel(7, $schoolYear)[0];
                        break;
                }
            }

            if($id != 0)
            {
                $levelName = $request->request->get('c'.$id);
                $coefficient = $request->request->get($id);
                $selectedUnrankedCoefficient = $this->unrankedCoefficientRepository->find($id);
            }
        }

        if($request->request->has('unrankedCoefficientUpdated'))
        {
            $level = $request->request->get('level');
            $id = $request->request->get('id');
            $coefficient = $request->request->get('coefficient');

            $application = (int)$request->request->get('application');
            $application = (bool)$application;

            $forMark = (int)$request->request->get('forMark');
            $forMark = (bool)$forMark;

            if($level != 0) 
            {
                switch ($level) 
                {
                    case 1:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(1, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                    case 2:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(2, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                    case 3:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(3, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                    case 4:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(4, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                    
                    case 5:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(5, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                        
                    case 6:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(6, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                    
                    case 7:
                        $unrankedCoefficients = $this->unrankedCoefficientRepository->findForClassroomsLevel(7, $schoolYear);
                        foreach ($unrankedCoefficients as $unrankedCoefficient) 
                        {
                            $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                                ->setForFirstGroup($application)
                                ->setForMark($forMark);
                            $this->em->persist($unrankedCoefficient);
                        }
                        break;
                }
                $this->em->flush();
                $this->addFlash('info', $this->translator->trans('Limit coefficient/mark updated successfully'));
                
            }

            if($id != 0)
            {
                $unrankedCoefficient = $this->unrankedCoefficientRepository->find($id);
                $unrankedCoefficient->setUnrankedCoefficient($coefficient)
                    ->setForFirstGroup($application);
                $this->em->flush();
                $this->addFlash('info', $this->translator->trans('Limit coefficient updated successfully'));
            }

            return $this->redirectToRoute('report_defineUnrankedCoefficient', [
                'notification' => 1,
            ]);
        }

        return $this->render('report/editUnrankedCoefficient.html.twig', [
            'levelName' =>$levelName,
            'coefficient' => $coefficient,
            'level' => $level,
            'id' => $id,
            'selectedUnrankedCoefficient' => $selectedUnrankedCoefficient,
            'school' => $school,
        ]);
        
        
    }
}
