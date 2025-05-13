<?php

namespace App\Controller\Administrator;

use App\Entity\User;
use App\Entity\ConstantsClass;
use App\Form\AdministratorType;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/administrators')]
class AddAdministratorController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected SchoolRepository $schoolRepository,
        protected UserPasswordHasherInterface $encoder, 
    )
    {}

    #[Route('/add-administrator', name: 'add_administrator')]
    public function addAministrator(Request $request): Response
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

        $administrator = new User;

        $form = $this->createForm(AdministratorType::class, $administrator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $administrator->setUsername($administrator->getFullName())
            ->setPassword($this->encoder->hashPassword($administrator, "admin"))
            ->setRoles([ConstantsClass::ROLE_ADMIN])
            ->setSlug(uniqid('', true));

            $this->em->persist($administrator);
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Administrator saved with success !'));
                
            $mySession->set('ajout', 1);

            $administrator = new User;
            $form = $this->createForm(AdministratorType::class, $administrator);
            
        }

        return $this->render('administrator/addAdministrator.html.twig', [
            'id' => 0,
            'school' => $school,
            'formAdmin' => $form->createView(),
        ]);
    }
}
