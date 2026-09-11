<?php

namespace App\Controller\Verrou;

use App\Repository\VerrouRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route("/verrou")]
class UnlockSchoolYearController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected VerrouRepository $verrouRepository,
    ) {}

    #[Route("/unlockSchoolYear", name:"verrou_unlockSchoolYear", methods: ['GET', 'POST'])]
    public function unlockSchoolYear(Request $request): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if (!$mySession) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans('Session expired. Please log in again.'),
                ], Response::HTTP_UNAUTHORIZED);
            }

            return $this->redirectToRoute("app_logout");
        }

        $schoolYear = $mySession->get('schoolYear');

        $verrou = $this->verrouRepository->findOneBySchoolYear($schoolYear);

        if (!$verrou) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans('Lock option not found.'),
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->redirectToRoute('verrou_displayLockOption');
        }

        $verrou->setVerrou(0);
        $this->em->flush();

        $mySession->set('verrou', $verrou);

        $message = $this->translator->trans('All changes unlocked with success !');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'locked' => false,
                'value' => 0,
                'message' => $message,
            ]);
        }

        $this->addFlash('info', $message);
        $mySession->set('miseAjour', 1);

        return $this->redirectToRoute('verrou_displayLockOption', ['m' => 1]);
    }
}
