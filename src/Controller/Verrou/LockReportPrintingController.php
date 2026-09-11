<?php

namespace App\Controller\Verrou;

use App\Repository\TermRepository;
use App\Repository\VerrouReportRepository;
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
class LockReportPrintingController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TermRepository $termRepository,
        protected TranslatorInterface $translator,
        protected VerrouReportRepository $verrouReportRepository,
    ) {}

    #[Route("/lockReportPrinting/{idT<[0-9]+>}/{lock<[0-1]{1}>}", name:"verrou_lockReportPrinting", methods: ['GET', 'POST'])]
    public function lockReportPrinting(Request $request, int $idT, int $lock): Response
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

        $selectedTerm = $this->termRepository->find($idT);

        if (!$selectedTerm) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans('Term not found.'),
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->redirectToRoute('verrou_displayLockOption');
        }

        $verrouReport = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $selectedTerm,
        ]);

        if (!$verrouReport) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans('Report printing lock option not found.'),
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->redirectToRoute('verrou_displayLockOption');
        }

        $locked = $lock === 1;
        $verrouReport->setVerrouReport($locked);
        $this->em->flush();

        $message = $locked
            ? $this->translator->trans('All printing of report of the selected term  is now locked')
            : $this->translator->trans('All printing of report of the selected term is now possible');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'locked' => $locked,
                'value' => $locked ? 1 : 0,
                'id' => $idT,
                'message' => $message,
            ]);
        }

        $this->addFlash('info', $message);
        $mySession->set('miseAjour', 1);

        return $this->redirectToRoute('verrou_displayLockOption', ['m' => 1]);
    }
}
