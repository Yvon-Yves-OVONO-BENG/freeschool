<?php

namespace App\Controller\Administrator;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/administrators')]
class ListAdministratorsController extends AbstractController
{
    public function __construct(
        protected UserRepository $userRepository,
        protected SchoolRepository $schoolRepository,
        protected EntityManagerInterface $em,
    )
    {}

    #[Route('/list-administrators/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}', name: 'list_administrators')]
    public function listAdministrators(Request $request,int $a = 0, int $m = 0, int $s = 0): Response
    {
        $mySession = $request->getSession();
         
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');
            
        }
        else 
        {
            return $this->redirectToRoute("app_logout");
        }

        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
            $mySession->set('saisiNotes', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            
        }

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $users = $this->userRepository->findAll();
        
        $administrators = [];

        foreach ($users as $user) 
        {
            // $user->setSlug(uniqid('', true));
            $user->setBloque(0);
            $user->setSupprime(0);
            $this->em->persist($user);
            $this->em->flush();

            if ((in_array(ConstantsClass::ROLE_ADMIN, $user->getRoles()) || in_array(ConstantsClass::ROLE_SUPER_ADMIN, $user->getRoles())) && $user->isSupprime() == 0) 
            {
                $administrators[] = $user;
            }
        }

        return $this->render('administrator/listAdministrators.html.twig', [
            'school' => $school,
            'administrators' => $administrators,
        ]);
    }
}
