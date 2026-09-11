<?php

namespace App\Controller\Grade;

use App\Repository\GradeRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/grade')]
class DeleteGradeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected GradeRepository $gradeRepository,
        protected SchoolYearService $schoolYearService,
    ) {}

    #[Route('/deleteGrade/{slug}', name: 'grade_deleteGrade')]
    public function deleteGrade(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $this->resetFlashSessionFlags($mySession);

        if (!$mySession) {
            return $this->redirectToRoute('app_logout');
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        $grade = $this->gradeRepository->findOneBy([
            'slug' => $slug,
        ]);

        if (!$grade) {
            return $this->redirectToRoute('page_error');
        }

        if ($grade->getTeachers()->count() > 0) {
            $this->addFlash('danger', $this->translator->trans('Delete denied. This rank is allowed to teacher !'));
            return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
        }

        $this->em->remove($grade);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Rank deleted with success !'));

        $mySession->set('suppression', 1);

        return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
    }

    #[Route('/bulkDeleteGrade', name: 'grade_bulkDeleteGrade', methods: ['POST'])]
    public function bulkDeleteGrade(Request $request): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if (!$mySession) {
            return $this->redirectToRoute('app_logout');
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        if (!$this->isCsrfTokenValid('bulk_delete_grade', $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('Invalid security token !'));
            return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
        }

        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
            && !$this->isGranted('ROLE_PROVISEUR')
        ) {
            throw $this->createAccessDeniedException($this->translator->trans('Access denied'));
        }

        $slugs = $request->request->all('gradeSlugs');

        if (!is_array($slugs)) {
            $slugs = [];
        }

        $slugs = array_values(array_unique(array_filter($slugs)));

        if (empty($slugs)) {
            $this->addFlash('danger', $this->translator->trans('No rank selected !'));
            return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
        }

        $grades = $this->gradeRepository->findBy([
            'slug' => $slugs,
        ]);

        if (empty($grades)) {
            $this->addFlash('danger', $this->translator->trans('No rank found !'));
            return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
        }

        if (count($grades) !== count($slugs)) {
            $this->addFlash('danger', $this->translator->trans('One or more selected ranks were not found !'));
            return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
        }

        foreach ($grades as $grade) {
            if ($grade->getTeachers()->count() > 0) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('Bulk delete denied. One or more selected ranks are allowed to teacher !')
                );

                return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
            }
        }

        $deletedCount = 0;

        foreach ($grades as $grade) {
            $this->em->remove($grade);
            $deletedCount++;
        }

        $this->em->flush();

        $this->addFlash(
            'info',
            $this->translator->trans('%count% rank(s) deleted with success !', [
                '%count%' => $deletedCount,
            ])
        );

        $mySession->set('suppression', 1);

        return $this->redirectToRoute('grade_displayGrade', ['s' => 1]);
    }

    private function resetFlashSessionFlags($mySession): void
    {
        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
    }
}