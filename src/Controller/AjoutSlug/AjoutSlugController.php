<?php

namespace App\Controller\AjoutSlug;

use App\Repository\BrowserRepository;
use App\Repository\DeviceTypeRepository;
use App\Repository\OperatingSystemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AjoutSlugController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected BrowserRepository $browserRepository,
        protected DeviceTypeRepository $deviceTypeRepository,
        protected OperatingSystemRepository $operatingSystemRepository,
    )
    {}

    #[Route('/ajout-slug-browser', name: 'ajout_slug_browser')]
    public function index(): Response
    {
        ///////////////
        $absenceTeachers = $this->browserRepository->findAll();

        foreach ($absenceTeachers as $absenceTeacher) 
        {
            $absenceTeacher->setSlug(uniqid(true, ''));
            $this->em->persist($absenceTeacher);
            
        }

        ////////////////
        $classrooms = $this->deviceTypeRepository->findAll();

        foreach ($classrooms as $classroom) 
        {
            $classroom->setSlug(uniqid(true, ''));
            $this->em->persist($classroom);
            
        }

        ////////////////
        $departments = $this->operatingSystemRepository->findAll();

        foreach ($departments as $department) 
        {
            $department->setSlug(uniqid(true, ''));
            $this->em->persist($department);
            
        }


        ////////////////////
        $this->em->flush();

        return $this->redirectToRoute('login');
    }
}
