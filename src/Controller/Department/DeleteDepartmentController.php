<?php

namespace App\Controller\Department;

use App\Repository\DepartmentRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/department')]
class DeleteDepartmentController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService,
        protected DepartmentRepository $departmentRepository,
    ) {}

    #[Route('/deleteDepartment/{slug}', name: 'department_deleteDepartment')]
    public function deleteDepartment(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();
        $this->resetSessionFlags($mySession);

        if ($mySession) {
            $schoolYear = $mySession->get('schoolYear');
        } else {
            return $this->redirectToRoute('app_logout');
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        $department = $this->departmentRepository->findOneBy([
            'slug' => $slug,
            'schoolYear' => $schoolYear,
        ]);

        if (!$department) {
            return $this->redirectToRoute('page_error');
        }

        if ($department->getSubjects()->count() > 0 || $department->getTeachers()->count() > 0) {
            $this->addFlash('info', $this->translator->trans('Impossible to delete a department with subjects or teachers'));

            return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
        }

        $this->em->remove($department);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Department deleted with success !'));

        $mySession->set('suppression', 1);

        return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
    }

    #[Route('/bulkDeleteDepartment', name: 'department_bulkDeleteDepartment', methods: ['POST'])]
    public function bulkDeleteDepartment(Request $request): Response
    {
        $mySession = $request->getSession();
        $this->resetSessionFlags($mySession);

        if ($mySession) {
            $schoolYear = $mySession->get('schoolYear');
        } else {
            return $this->redirectToRoute('app_logout');
        }

        $verrou = $mySession->get('verrou');

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        if (!$this->isCsrfTokenValid('bulk_delete_department', $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('Invalid security token !'));

            return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
        }

        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
            && !$this->isGranted('ROLE_PROVISEUR')
        ) {
            throw $this->createAccessDeniedException($this->translator->trans('Access denied'));
        }

        $slugs = $request->request->all('departmentSlugs');

        if (!is_array($slugs)) {
            $slugs = [];
        }

        $slugs = array_values(array_unique(array_filter($slugs)));

        if (empty($slugs)) {
            $this->addFlash('danger', $this->translator->trans('No department selected !'));

            return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
        }

        $departments = $this->departmentRepository->findBy([
            'slug' => $slugs,
            'schoolYear' => $schoolYear,
        ]);

        if (empty($departments)) {
            $this->addFlash('danger', $this->translator->trans('No department found !'));

            return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
        }

        if (count($departments) !== count($slugs)) {
            $this->addFlash('danger', $this->translator->trans('One or more selected departments were not found !'));

            return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
        }

        foreach ($departments as $department) {
            if ($department->getSubjects()->count() > 0 || $department->getTeachers()->count() > 0) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('Bulk delete denied. One or more selected departments contain subjects or teachers !')
                );

                return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
            }
        }

        $deletedCount = 0;

        foreach ($departments as $department) {
            $this->em->remove($department);
            $deletedCount++;
        }

        $this->em->flush();

        $this->addFlash(
            'info',
            $this->translator->trans('%count% department(s) deleted with success !', [
                '%count%' => $deletedCount,
            ])
        );

        $mySession->set('suppression', 1);

        return $this->redirectToRoute('department_displayDepartment', ['s' => 1]);
    }

    private function resetSessionFlags($mySession): void
    {
        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
    }
}
