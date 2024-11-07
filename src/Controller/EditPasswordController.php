<?php

namespace App\Controller;

use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */ 
class EditPasswordController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository)
    {}

    #[Route("/edit-password", name:"edit_password")]
    public function editPassword(Request $request): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        return $this->render('edit_password/index.html.twig', [
            'school' => $school
        ]);
    }
}
