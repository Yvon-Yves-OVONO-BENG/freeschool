<?php

namespace App\Controller\Statistic;

use App\Entity\ConstantsClass;
use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class RateOfPresencePerClassController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected SchoolRepository $schoolRepository,
        )
    {}

    #[Route("/rateOfPresencePerClass", name:"statistic_rateOfPresencePerClass")]
    public function rateOfPresencePerClass(Request $request): Response
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

        // on recupèere toutes les classes
        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);

        return $this->render('statistic/rateOfPresencePerClass.html.twig', [
            'terms' => $terms,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
            'school' => $school,
        ]);
    }

}
