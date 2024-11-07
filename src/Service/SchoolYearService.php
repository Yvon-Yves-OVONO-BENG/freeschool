<?php

namespace App\Service;

use App\Entity\NextYear;
use App\Entity\Verrou;
use App\Entity\SchoolYear;
use App\Repository\NextYearRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class SchoolYearService
{
    public function __construct(
        protected RequestStack $request, 
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        private NextYearRepository $nextYearRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    public function getNextSchoolYear(): SchoolYear
    {
        $mySession = $this->request->getSession();
        $schoolYear = $mySession->get('schoolYear');

        $schoolYearName = $schoolYear->getSchoolYear();
       
        $schoolYearExplode = explode('-', $schoolYearName);
        
        $year1 = $schoolYearExplode[1];
        $year2 = (int)$year1 + 1;

        $nextYearSchoolName = $year1.'-'.$year2;
        

        /////////////////
        $nextYearSchoolName = $year1.'-'.$year2;

        // $schoolYearExplode1 = explode('-', $nextYearSchoolName);
        
        // $year3 = $schoolYearExplode1[1];
        // $year4 = (int)$year3 + 1;

        // $nextYearSchoolName1 = $year3.'-'.$year4;
        
        $schoolYear = $this->schoolYearRepository->findOneBySchoolYear($nextYearSchoolName);
        // $nextYear = $this->nextYearRepository->findOneByNextYear($nextYearSchoolName);

        

        if (!$schoolYear) 
        {
            $schoolYear = new SchoolYear;
            $schoolYear->setSchoolYear($nextYearSchoolName);

            // $nextYear->setNextYear($nextYearSchoolName1);

            $this->em->persist($schoolYear);
            $this->em->flush();
        }
        
        // $nextYearSchoolNam = new NextYear;
        
        // $nextYearSchoolNam->setNextYear($nextYearSchoolName);
      
        return  $this->schoolYearRepository->findOneBySchoolYear($nextYearSchoolName);

    }

    public function getAccess(Verrou $verrou): bool
    {
        if($verrou->isVerrou())
        {
            /**
             * @var FlashBag
             */
            $flashBag = $this->request->getSession()->getBag('flashes');

            $flashBag->add("info", $this->translator->trans('Access denied. All changes are locked'));

            return false;
        }

        return true;
        
    }
}