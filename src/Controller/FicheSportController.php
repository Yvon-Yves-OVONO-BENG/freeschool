<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\ImpressionFicheSport;
use App\Service\ImpressionFicheSportObc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class FicheSportController extends AbstractController
{
    private const TYPE_LEVEL_4 = 'level4';
    private const TYPE_OBC = 'obc';

    public function __construct(
        protected StudentRepository $eleveRepository,
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classeRepository,
        protected ImpressionFicheSport $impressionFicheSport,
        protected ImpressionFicheSportObc $impressionFicheSportObc,
    ) {
    }

    #[Route(path: '/fiche-sport', name: 'fiche_sport', methods: ['GET'])]
    public function ficheSport(Request $request): Response
    {
        return $this->displaySportSheets($request, self::TYPE_LEVEL_4);
    }

    #[Route(path: '/fiche-sport/obc', name: 'fiche_sport_obc', methods: ['GET'])]
    public function ficheSportObc(Request $request): Response
    {
        return $this->displaySportSheets($request, self::TYPE_OBC);
    }

    #[Route(
        path: '/fiche-sport/{type}/imprimer',
        name: 'fiche_sport_print',
        methods: ['POST'],
        requirements: ['type' => 'level4|obc']
    )]
    public function printSportSheets(Request $request, string $type): Response
    {
        $context = $this->academicContext($request);
        if ($context === null) {
            return $this->redirectToRoute('app_logout');
        }

        [$schoolYear, $subSystem] = $context;
        $classroom = $this->classeRepository->findOneBy([
            'slug' => trim((string) $request->request->get('classroom')),
        ]);
        $returnRoute = $type === self::TYPE_OBC ? 'fiche_sport_obc' : 'fiche_sport';

        if (!$this->isAllowedClassroom($classroom, $schoolYear, $subSystem, $type)) {
            $this->addFlash('danger', 'La classe sélectionnée ne correspond pas aux niveaux autorisés.');

            return $this->redirectToRoute($returnRoute);
        }

        if (!$this->isCsrfTokenValid(
            'sport_print_'.$classroom->getId().'_'.$type,
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $mode = (string) $request->request->get('print_mode', 'selected');
        $studentSlugs = [];

        if ($mode !== 'all') {
            $requestData = $request->request->all();
            $studentSlugs = array_values(array_filter(array_map(
                static fn ($slug): string => trim((string) $slug),
                (array) ($requestData['student_slugs'] ?? [])
            )));

            if ($studentSlugs === []) {
                $this->addFlash('warning', 'Sélectionnez au moins un élève avant de lancer l’impression.');

                return $this->redirectToRoute($returnRoute, ['classe' => $classroom->getSlug()]);
            }
        }

        $students = $this->eleveRepository->findForSportSheets(
            $classroom,
            $schoolYear,
            $subSystem,
            $studentSlugs
        );

        if ($students === []) {
            $this->addFlash('warning', 'Aucun élève valide n’a été trouvé pour cette impression.');

            return $this->redirectToRoute($returnRoute, ['classe' => $classroom->getSlug()]);
        }

        return $this->pdfResponse($students, $classroom, $schoolYear, $subSystem, $type);
    }

    #[Route(
        path: '/fiche-sport/{type}/imprimer/{classroomSlug}/{studentSlug}',
        name: 'fiche_sport_print_one',
        methods: ['GET'],
        requirements: [
            'type' => 'level4|obc',
            'classroomSlug' => '[^/]+',
            'studentSlug' => '[^/]+',
        ]
    )]
    public function printOneSportSheet(
        Request $request,
        string $type,
        string $classroomSlug,
        string $studentSlug
    ): Response {
        $context = $this->academicContext($request);
        if ($context === null) {
            return $this->redirectToRoute('app_logout');
        }

        [$schoolYear, $subSystem] = $context;
        $classroom = $this->classeRepository->findOneBy(['slug' => $classroomSlug]);

        if (!$this->isAllowedClassroom($classroom, $schoolYear, $subSystem, $type)) {
            throw $this->createNotFoundException('Classe introuvable pour cette fiche sport.');
        }

        $students = $this->eleveRepository->findForSportSheets(
            $classroom,
            $schoolYear,
            $subSystem,
            [$studentSlug]
        );

        if (count($students) !== 1) {
            throw $this->createNotFoundException('Élève introuvable dans cette classe.');
        }

        return $this->pdfResponse($students, $classroom, $schoolYear, $subSystem, $type);
    }

    private function displaySportSheets(Request $request, string $type): Response
    {
        $context = $this->academicContext($request);
        if ($context === null) {
            return $this->redirectToRoute('app_logout');
        }

        [$schoolYear, $subSystem] = $context;
        $levels = $this->allowedLevels($type);
        $classes = $this->classeRepository->findForSportSheets($schoolYear, $subSystem, $levels);
        $selectedClassroom = null;
        $students = [];
        $classroomSlug = trim((string) $request->query->get('classe', ''));

        if ($classroomSlug !== '') {
            $candidate = $this->classeRepository->findOneBy(['slug' => $classroomSlug]);

            if ($this->isAllowedClassroom($candidate, $schoolYear, $subSystem, $type)) {
                $selectedClassroom = $candidate;
                $students = $this->eleveRepository->findForSportSheets(
                    $candidate,
                    $schoolYear,
                    $subSystem
                );
            } else {
                $this->addFlash('danger', 'Cette classe n’est pas disponible pour le type de fiche demandé.');
            }
        }

        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        return $this->render('sport/sportAfficher.html.twig', [
            'classes' => $classes,
            'school' => $school,
            'schoolYear' => $schoolYear,
            'sessionYear' => $this->sessionYear($schoolYear),
            'selectedClassroom' => $selectedClassroom,
            'students' => $students,
            'sheetType' => $type,
            'allowedLevels' => $levels,
        ]);
    }

    /**
     * @return array{0: SchoolYear, 1: SubSystem}|null
     */
    private function academicContext(Request $request): ?array
    {
        $session = $request->getSession();
        $schoolYear = $session->get('schoolYear');
        $subSystem = $session->get('subSystem');

        if (!$schoolYear instanceof SchoolYear || !$subSystem instanceof SubSystem) {
            return null;
        }

        return [$schoolYear, $subSystem];
    }

    private function isAllowedClassroom(
        ?Classroom $classroom,
        SchoolYear $schoolYear,
        SubSystem $subSystem,
        string $type
    ): bool {
        if (
            $classroom === null
            || $classroom->getSchoolYear() === null
            || $classroom->getSubSystem() === null
            || $classroom->getLevel() === null
        ) {
            return false;
        }

        return $classroom->getSchoolYear()->getId() === $schoolYear->getId()
            && $classroom->getSubSystem()->getId() === $subSystem->getId()
            && in_array((int) $classroom->getLevel()->getLevel(), $this->allowedLevels($type), true);
    }

    /**
     * @return int[]
     */
    private function allowedLevels(string $type): array
    {
        return $type === self::TYPE_OBC ? [6, 7] : [4];
    }

    private function sessionYear(SchoolYear $schoolYear): string
    {
        preg_match_all('/(?:19|20)\\d{2}/', (string) $schoolYear->getSchoolYear(), $matches);

        if (!empty($matches[0])) {
            $years = $matches[0];

            return (string) end($years);
        }

        return (string) $schoolYear->getSchoolYear();
    }

    /**
     * @param array<int, object> $students
     */
    private function pdfResponse(
        array $students,
        Classroom $classroom,
        SchoolYear $schoolYear,
        SubSystem $subSystem,
        string $type
    ): Response {
        /** @var School|null $school */
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        if ($type === self::TYPE_OBC) {
            $pdf = $this->impressionFicheSportObc->impressionFiche(
                $students,
                $classroom,
                $schoolYear,
                $school
            );
            $prefix = 'Fiches-EPS-OBC';
        } else {
            $pdf = $this->impressionFicheSport->impresionFiche(
                $students,
                $classroom,
                $schoolYear,
                $school
            );
            $prefix = $subSystem->getId() === 1 ? 'Sport-sheets' : 'Fiches-de-sport';
        }

        if (count($students) === 1) {
            $prefix = $type === self::TYPE_OBC
                ? 'Fiche-EPS-OBC'
                : ($subSystem->getId() === 1 ? 'Sport-sheet' : 'Fiche-de-sport');
            $target = $this->safeFilePart((string) $students[0]->getFullName());
        } else {
            $target = $this->safeFilePart((string) $classroom->getClassroom());
        }

        $filename = trim($prefix.'-'.$target, '-').'.pdf';

        return new Response(
            $pdf->Output($filename, 'I'),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function safeFilePart(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($value));
        $ascii = $ascii === false ? $value : $ascii;
        $safe = preg_replace('/[^A-Za-z0-9]+/', '-', $ascii);

        return trim((string) $safe, '-') ?: 'fiche-sport';
    }
}
