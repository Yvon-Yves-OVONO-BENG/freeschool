<?php

namespace App\Controller\School;


use Imagine\Image\Box;
use App\Form\SchoolType;
use Imagine\Gd\Imagine;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EducationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/school")]
class EditSchoolController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EducationRepository $educationRepository,
        )
    {}

    #[Route("/editSchool", name:"school_editSchool")]
    public function editSchool(Request $request): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $form = $this->createForm(SchoolType::class, $school);
        $form->handleRequest($request);

        $imagine = new Imagine;
        
        if($form->isSubmitted() && $form->isValid())
        {
            /// je recupere le type établissement et si l'établissement est Lycee ou CES dans le formulaire 
            $public = $request->request->get('public');
            $lycee = $request->request->get('lycee');
            $education = $request->request->get('education');

            $education = $this->educationRepository->find($education);

            // dd($school->getLogo());
            if($school->getLogo())
            {
                $imagine->open(getcwd().'/images/school/'.$school->getLogo())->resize(new Box(170, 171))->save(getcwd().'/images/school/'.$school->getLogo());
            }

            if($school->getFiligree() )
            {
                $imagine->open(getcwd().'/images/school/'.$school->getFiligree())->resize(new Box(447, 412))->save(getcwd().'/images/school/'.$school->getFiligree());
            }

            $school->setPublic($public)->setLycee($lycee)->setEducation($education);

            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('School updated with success !'));

            $mySession->set('miseAjour', 1);

            // on met à jour l'établissement dans la session
            $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

            $mySession = $request->getSession();
            $mySession->set('school',$school);
    
        }

        $numberOfTeachers = count($this->teacherRepository->findAllToDisplay($schoolYear, $subSystem));
        $numberOfClassrooms = count($this->classroomRepository->findAllToDisplay($schoolYear, $subSystem));
        $numberOfStudentInSchool = count($this->studentRepository->findBy(['schoolYear' => $schoolYear]));

        return $this->render('school/editSchool.html.twig', [
            'school' => $school,
            'formSchool' => $form->createView(),
            'numberOfTeachers' => $numberOfTeachers,
            'numberOfClassrooms' => $numberOfClassrooms,
            'numberOfStudentInSchool' => $numberOfStudentInSchool,
        ]);
    }
}
