<?php

namespace App\Controller\Diploma;

use App\Service\SchoolYearService;
use App\Repository\DiplomaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/diploma')]
class DeleteDiplomaController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService,
        protected DiplomaRepository $diplomaRepository,
    ) {}

    #[Route('/deletediploma/{slug}', name: 'diploma_deleteDiploma')]
    public function deleteDiploma(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $this->resetSessionFlags($mySession);

        if (!$mySession) {
            return $this->redirectToRoute('app_logout');
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        $diploma = $this->diplomaRepository->findOneBy([
            'slug' => $slug,
        ]);

        if (!$diploma) {
            return $this->redirectToRoute('page_error');
        }

        if ($diploma->getTeachers()->count() > 0) {
            $this->addFlash(
                'danger',
                $this->translator->trans('Delete denied. This diploma is allowed to teacher !')
            );

            return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
        }

        $this->em->remove($diploma);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Diploma deleted with success !'));

        $mySession->set('suppression', 1);

        return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
    }

    #[Route('/bulkDeleteDiploma', name: 'diploma_bulkDeleteDiploma', methods: ['POST'])]
    public function bulkDeleteDiploma(Request $request): Response
    {
        $mySession = $request->getSession();

        $this->resetSessionFlags($mySession);

        if (!$mySession) {
            return $this->redirectToRoute('app_logout');
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        if (!$this->isCsrfTokenValid('bulk_delete_diploma', $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('Invalid security token !'));

            return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
        }

        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
            && !$this->isGranted('ROLE_PROVISEUR')
        ) {
            throw $this->createAccessDeniedException($this->translator->trans('Access denied'));
        }

        $slugs = $request->request->get('diplomaSlugs', []);

        if (!is_array($slugs)) {
            $slugs = [];
        }

        $slugs = array_values(array_unique(array_filter($slugs)));

        if (empty($slugs)) {
            $this->addFlash('danger', $this->translator->trans('No diploma selected !'));

            return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
        }

        $diplomas = $this->diplomaRepository->findBy([
            'slug' => $slugs,
        ]);

        if (empty($diplomas)) {
            $this->addFlash('danger', $this->translator->trans('No diploma found !'));

            return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
        }

        if (count($diplomas) !== count($slugs)) {
            $this->addFlash('danger', $this->translator->trans('One or more selected diplomas were not found !'));

            return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
        }

        foreach ($diplomas as $diploma) {
            if ($diploma->getTeachers()->count() > 0) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('Bulk delete denied. One or more selected diplomas are allowed to teacher !')
                );

                return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
            }
        }

        $deletedCount = 0;

        foreach ($diplomas as $diploma) {
            $this->em->remove($diploma);
            $deletedCount++;
        }

        $this->em->flush();

        $this->addFlash(
            'info',
            $this->translator->trans('%count% diploma(s) deleted with success !', [
                '%count%' => $deletedCount,
            ])
        );

        $mySession->set('suppression', 1);

        return $this->redirectToRoute('diploma_displayDiploma', ['s' => 1]);
    }

    private function resetSessionFlags($mySession): void
    {
        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
    }
}
