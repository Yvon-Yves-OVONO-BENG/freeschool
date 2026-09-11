<?php

namespace App\Controller\Grade;

use App\Entity\Grade;
use App\Form\GradeType;
use App\Service\SchoolYearService;
use App\Repository\GradeRepository;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/grade')]
class SaveGradeController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected GradeRepository $gradeRepository, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route('/saveGrade', name: 'grade_saveGrade')]
public function saveGrade(Request $request): Response
{
    $mySession = $request->getSession();

    $mySession->set('ajout', null);
    $mySession->set('suppression', null);
    $mySession->set('miseAjour', null);
    $mySession->set('saisiNotes', null);

    if ($mySession) {
        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');
    } else {
        return $this->redirectToRoute('app_logout');
    }

    $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
    $verrou = $mySession->get('verrou');
    $slug = 0;

    if (!$this->schoolYearService->getAccess($verrou)) {
        return $this->redirectToRoute('home_mainMenu');
    }

    $grade = new Grade();
    $form = $this->createForm(GradeType::class, $grade);
    $form->handleRequest($request);

    if ($request->isMethod('POST')) {
        if (!$this->isCsrfTokenValid('bulk_save_grade', $request->request->get('_token_bulk_ranks'))) {
            $this->addFlash('danger', $this->translator->trans('Invalid security token !'));
            return $this->redirectToRoute('grade_saveGrade');
        }

        $submittedRanks = $this->extractSubmittedRanks($request, $form->getName());

        if (!empty($submittedRanks)) {
            $normalizedRanks = [];

            foreach ($submittedRanks as $rankName) {
                $rankName = strtoupper(trim($rankName));

                if ($rankName !== '') {
                    $normalizedRanks[] = $rankName;
                }
            }

            $normalizedRanks = array_values(array_unique($normalizedRanks));

            foreach ($normalizedRanks as $rankName) {
                $existingGrade = $this->gradeRepository->findOneBy([
                    'grade' => $rankName,
                ]);

                if ($existingGrade) {
                    $this->addFlash(
                        'danger',
                        $this->translator->trans('This rank already exists: ') . $rankName
                    );

                    $grades = $this->gradeRepository->findBy([], ['grade' => 'ASC']);

                    return $this->render('grade/saveGrade.html.twig', [
                        'slug' => $slug,
                        'grades' => $grades,
                        'formGrade' => $form->createView(),
                        'school' => $school,
                    ]);
                }
            }

            $savedCount = 0;

            foreach ($normalizedRanks as $rankName) {
                $newGrade = new Grade();

                $dernierGrade = $this->gradeRepository->findBy([], ['id' => 'DESC'], 1, 0);
                $id = $dernierGrade ? $dernierGrade[0]->getId() + $savedCount + 1 : $savedCount + 1;

                $newGrade
                    ->setGrade($rankName)
                    ->setSlug($this->generateGradeSlug($id));

                $this->em->persist($newGrade);
                $savedCount++;
            }

            $this->em->flush();

            if ($savedCount === 1) {
                $this->addFlash('info', $this->translator->trans('Rank saved with success !'));
            } else {
                $this->addFlash(
                    'info',
                    $this->translator->trans('%count% ranks saved with success !', [
                        '%count%' => $savedCount,
                    ])
                );
            }

            $mySession->set('ajout', 1);

            $grade = new Grade();
            $form = $this->createForm(GradeType::class, $grade);
        }
    }

    $grades = $this->gradeRepository->findBy([], ['grade' => 'ASC']);

    $slug = 0;

    return $this->render('grade/saveGrade.html.twig', [
        'slug' => $slug,
        'grades' => $grades,
        'formGrade' => $form->createView(),
        'school' => $school,
    ]);
}

private function extractSubmittedRanks(Request $request, string $formName): array
{
    $allPostData = $request->request->all();

    $formData = $allPostData[$formName] ?? [];
    $mainRank = $formData['grade'] ?? '';

    $extraRanks = $allPostData['extraGrades'] ?? [];

    if (!is_array($extraRanks)) {
        $extraRanks = [];
    }

    return array_filter(array_merge([$mainRank], $extraRanks), function ($rank) {
        return trim((string) $rank) !== '';
    });
}

private function generateGradeSlug(int $id): string
{
    $characts = 'abcdefghijklmnopqrstuvwxyz';
    $characts .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characts .= '1234567890';

    $slug = '';

    for ($i = 0; $i < 15; $i++) {
        $slug .= substr($characts, random_int(0, strlen($characts) - 1), 1);
    }

    return $slug . $id;
}

}
