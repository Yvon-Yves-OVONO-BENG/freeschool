<?php

namespace App\Controller\Diploma;

use App\Entity\Diploma;
use App\Form\DiplomaType;
use App\Service\SchoolYearService;
use App\Repository\DiplomaRepository;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/diploma')]
class SaveDiplomaController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        protected DiplomaRepository $diplomaRepository,
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService,
    ) {}

    #[Route('/saveDiploma', name: 'diploma_saveDiploma')]
    public function saveDiploma(Request $request): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if ($mySession) {
            $schoolYear = $mySession->get('schoolYear');
        } else {
            return $this->redirectToRoute('app_logout');
        }

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        $verrou = $mySession->get('verrou');
        $slug = 0;

        if (!$this->schoolYearService->getAccess($verrou)) {
            return $this->redirectToRoute('home_mainMenu');
        }

        $diploma = new Diploma();
        $form = $this->createForm(DiplomaType::class, $diploma);
        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('bulk_save_diploma', $request->request->get('_token_bulk_diplomas'))) {
                $this->addFlash('danger', $this->translator->trans('Invalid security token !'));

                return $this->redirectToRoute('diploma_saveDiploma');
            }

            $submittedDiplomas = $this->extractSubmittedDiplomas($request, $form->getName());

            if (!empty($submittedDiplomas)) {
                $normalizedDiplomas = [];

                foreach ($submittedDiplomas as $diplomaName) {
                    $diplomaName = strtoupper(trim((string) $diplomaName));

                    if ($diplomaName !== '') {
                        $normalizedDiplomas[] = $diplomaName;
                    }
                }

                $normalizedDiplomas = array_values(array_unique($normalizedDiplomas));

                foreach ($normalizedDiplomas as $diplomaName) {
                    $existingDiploma = $this->diplomaRepository->findOneBy([
                        'diploma' => $diplomaName,
                    ]);

                    if ($existingDiploma) {
                        $this->addFlash(
                            'danger',
                            $this->translator->trans('This diploma already exists: ') . $diplomaName
                        );

                        $diplomas = $this->diplomaRepository->findBy([], ['diploma' => 'ASC']);

                        return $this->render('diploma/saveDiploma.html.twig', [
                            'slug' => $slug,
                            'diplomas' => $diplomas,
                            'formDiploma' => $form->createView(),
                            'school' => $school,
                        ]);
                    }
                }

                $savedCount = 0;

                foreach ($normalizedDiplomas as $diplomaName) {
                    $newDiploma = new Diploma();

                    $lastDiploma = $this->diplomaRepository->findBy([], ['id' => 'DESC'], 1, 0);
                    $id = $lastDiploma ? $lastDiploma[0]->getId() + $savedCount + 1 : $savedCount + 1;

                    $newDiploma
                        ->setDiploma($diplomaName)
                        ->setSlug($this->generateDiplomaSlug($id));

                    $this->em->persist($newDiploma);
                    $savedCount++;
                }

                $this->em->flush();

                if ($savedCount === 1) {
                    $this->addFlash('info', $this->translator->trans('Diploma saved with success !'));
                } else {
                    $this->addFlash(
                        'info',
                        $this->translator->trans('%count% diplomas saved with success !', [
                            '%count%' => $savedCount,
                        ])
                    );
                }

                $mySession->set('ajout', 1);

                $diploma = new Diploma();
                $form = $this->createForm(DiplomaType::class, $diploma);
            }
        }

        $diplomas = $this->diplomaRepository->findBy([], ['diploma' => 'ASC']);

        return $this->render('diploma/saveDiploma.html.twig', [
            'slug' => $slug,
            'diplomas' => $diplomas,
            'formDiploma' => $form->createView(),
            'school' => $school,
        ]);
    }

    private function extractSubmittedDiplomas(Request $request, string $formName): array
    {
        $allPostData = $request->request->all();

        $formData = $allPostData[$formName] ?? [];
        $mainDiploma = $formData['diploma'] ?? '';

        $extraDiplomas = $allPostData['extraDiplomas'] ?? [];

        if (!is_array($extraDiplomas)) {
            $extraDiplomas = [];
        }

        return array_filter(array_merge([$mainDiploma], $extraDiplomas), function ($diploma) {
            return trim((string) $diploma) !== '';
        });
    }

    private function generateDiplomaSlug(int $id): string
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
