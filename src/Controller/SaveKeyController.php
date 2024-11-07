<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux utilisateurs")
 *
 */
class SaveKeyController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected SchoolRepository $schoolRepository,
        protected UserPasswordHasherInterface $encoder, 
        )
    {}

    #[Route("/save-key", name:"save_key")]
    public function saveKey(Request $request): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $activationKey = new User;

        $characts   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';	
        $characts   .= '1234567890';
        $myKey1      = ''; 
        $myKey2      = ''; 
        $myKey3      = ''; 
        $myKey4      = ''; 
        $myKey5      = ''; 

        for($i=0;$i < 5;$i++) 
        { 
            $myKey1 .= substr($characts,rand()%(strlen($characts)),1); 
            $myKey2 .= substr($characts,rand()%(strlen($characts)),1); 
            $myKey3 .= substr($characts,rand()%(strlen($characts)),1); 
            $myKey4 .= substr($characts,rand()%(strlen($characts)),1); 
            $myKey5 .= substr($characts,rand()%(strlen($characts)),1); 
        }

        ///je hash ma clé
        $hash = $this->encoder->hashPassword(
            $activationKey, 
            $myKey1.'-'.
            $myKey2.'-'.
            $myKey3.'-'.
            $myKey4.'-'.
            $myKey5
        );

        ////je set ma clé
        $activationKey->setActivationKey($hash);
        $activationKey->setCleEnClaire(
            $myKey1.'-'.
            $myKey2.'-'.
            $myKey3.'-'.
            $myKey4.'-'.
            $myKey5);
        
        $this->em->persist($activationKey);
        $this->em->flush();

        return $this->render('save_key/save.html.twig', [
            'school' => $school
        ]);
    }
}
