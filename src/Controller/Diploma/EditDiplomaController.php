<?php

namespace App\Controller\Diploma;

use App\Form\DiplomaType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DiplomaRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/diploma")]
class EditDiplomaController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected DiplomaRepository $diplomaRepository, 
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route("/editDiploma/{slug}", name:"diploma_editDiploma")]
    public function saveDiploma(Request $request, string $slug): Response
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

        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $diploma = $this->diplomaRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(DiplomaType::class, $diploma);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $diploma->setDiploma(strtoupper($diploma->getDiploma()));

            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Diploma updated with success !'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des diplomes
            return $this->redirectToRoute('diploma_displayDiploma', [ 'm' => 1]);
            
        }

        $diplomas = $this->diplomaRepository->findBy([], ['diploma' => 'ASC']);

        return $this->render('diploma/saveDiploma.html.twig', [
            'formDiploma' => $form->createView(),
            'slug' => $slug,
            'diplomas' => $diplomas,
            'school' => $school,
            ]);
    }

}
