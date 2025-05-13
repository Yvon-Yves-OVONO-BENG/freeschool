<?php

namespace App\Controller\SuperAdmin;

use App\Entity\School;
use App\Form\SchoolType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/super-admin")
 */
class AddSchoolController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em, protected TranslatorInterface $translator)
    {
    }

    /**
     * @Route("/add-school/{id<[0-9]+>}", name="super_admin_addSchool")
     */
    public function addSchool(Request $request, int $id = 0): Response
    {
        $school = new School();

        $form = $this->createForm(SchoolType::class, $school);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) 
        {
            if($school->getId()) // Si le id existe alors c'est une modification
            {
                $this->em->flush(); // On modifie
                $this->addFlash('info', $this->translator->trans('School updated with success !'));

            }else // Si le id n'existe pas alors c'est un ajoût
            {
                // On ajoute dans la BD
                $this->em->persist($school);
                $this->em->flush(); 

                $id = $school->getId();
                $this->addFlash('info', $this->translator->trans('School saved with success !'));
            }
        }
        
        return $this->render('super_admin/saveSchool.html.twig', [
            'formSchool' => $form->createView(),
            'id' => $id
            ]);
    }
}
