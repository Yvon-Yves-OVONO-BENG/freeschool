<?php

namespace App\Controller\Timetable;

use App\Service\SchoolYearService;
use App\Repository\TimeTableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route("/timetable")]
class DeleteTimeTableController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService,
        protected TimeTableRepository $timeTableRepository,
    ) {
    }

    #[Route('/delete-time-table/{slug}', name: 'delete_time_table', methods: ['GET', 'POST', 'DELETE'])]
    public function deleteTimeTable(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if (!$mySession) {
            if ($this->isAjax($request)) {
                return new JsonResponse(['success' => false, 'message' => 'Session expired.'], 401);
            }

            return $this->redirectToRoute("app_logout");
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            if ($this->isAjax($request)) {
                return new JsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
            }

            return $this->redirectToRoute('home_mainMenu');
        }

        $timeTable = $this->timeTableRepository->findOneBy(['slug' => $slug]);

        if (!$timeTable) {
            if ($this->isAjax($request)) {
                return new JsonResponse(['success' => false, 'message' => 'Time table not found.'], 404);
            }

            return $this->redirectToRoute('page_error');
        }

        $deletedId = $timeTable->getId();
        $classroomSlug = $timeTable->getClassroom() ? $timeTable->getClassroom()->getSlug() : null;

        $this->em->remove($timeTable);
        $this->em->flush();

        $message = $this->translator->trans('Time table deleted with success !');

        if ($this->isAjax($request)) {
            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'id' => $deletedId,
                'classroomSlug' => $classroomSlug,
            ]);
        }

        $this->addFlash('info', $message);
        $mySession->set('suppression', 1);

        if ($classroomSlug) {
            return $this->redirectToRoute('display_time_table', [
                'slug' => $classroomSlug,
                's' => 1,
            ]);
        }

        return $this->redirectToRoute('display_time_table', ['s' => 1]);
    }

    private function isAjax(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || $request->headers->get('X-Requested-With') === 'XMLHttpRequest'
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }
}
