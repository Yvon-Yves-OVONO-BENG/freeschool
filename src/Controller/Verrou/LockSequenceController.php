<?php

namespace App\Controller\Verrou;

use App\Repository\SequenceRepository;
use App\Repository\VerrouSequenceRepository;
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
class LockSequenceController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected SequenceRepository $sequenceRepository,
        protected VerrouSequenceRepository $verrouSequenceRepository,
    ) {}

    #[Route("/lockSequence/{idS<[0-9]+>}/{lock<[0-1]{1}>}", name:"verrou_lockSequence", methods: ['GET', 'POST'])]
    public function lockSequence(Request $request, int $idS, int $lock): Response
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

        $selectedSequence = $this->sequenceRepository->find($idS);

        if (!$selectedSequence) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans('Sequence not found.'),
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->redirectToRoute('verrou_displayLockOption');
        }

        $verrouSequence = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $selectedSequence,
        ]);

        if (!$verrouSequence) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans('Sequence lock option not found.'),
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->redirectToRoute('verrou_displayLockOption');
        }

        $locked = $lock === 1;
        $verrouSequence->setVerrouSequence($locked);
        $this->em->flush();

        $message = $locked
            ? $this->translator->trans('Sequence lock with success !')
            : $this->translator->trans('Sequence unlock with success !');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'locked' => $locked,
                'value' => $locked ? 1 : 0,
                'id' => $idS,
                'message' => $message,
            ]);
        }

        $this->addFlash('info', $message);
        $mySession->set('miseAjour', 1);

        return $this->redirectToRoute('verrou_displayLockOption', ['m' => 1]);
    }
}
