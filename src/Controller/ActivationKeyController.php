<?php

namespace App\Controller;

use App\Repository\NextYearRepository;
use App\Repository\SchoolRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux utilisateurs")
 *
 */
class ActivationKeyController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected UserRepository $userRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected UserPasswordHasherInterface $encoder, 
        protected NextYearRepository $nextYearRepository, 
        )
    {}

    #[Route("/activation-key", name:"activation_key")]
    public function activationKey(Request $request): Response
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

        if($request->request->has('myButton'))
        {
            $key = $this->userRepository->findOneBy([
                'cleEnClaire' => $request->request->get('key')
            ]);
            
            $nextSchoolYear = $this->nextYearRepository->findAll();
           
            $schoolYearName = $nextSchoolYear[0]->getNextYear();
        
            $schoolYearExplode = explode('-', $schoolYearName);
            
            $year1 = $schoolYearExplode[1];
            $year2 = (int)$year1 + 1;

            $nextYearName = $year1.'-'.$year2;
            // dd($nextSchoolYear[0]); 
            $nextSchoolYear[0]->setNextYear($nextYearName);
            
            $this->em->persist($nextSchoolYear[0]);
            $this->em->remove($key);
            $this->em->flush();

            // $this->addFlash('info', 'Utilisateur ajouté avec succès et son mot de passe est :'.$code_aleatoire);
            $this->addFlash('info', $this->translator->trans('License activated successfully !'));

            return $this->redirectToRoute('home_dashboard');
        }

        return $this->render('activation_key/activation.html.twig', [
            'school' => $school
        ]);
    }
}
