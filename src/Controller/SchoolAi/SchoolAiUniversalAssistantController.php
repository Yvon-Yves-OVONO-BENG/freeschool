<?php

namespace App\Controller\SchoolAi;

use App\Entity\AiQueryHistory;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolAi\SchoolAiAnswerFormatter;
use App\Service\SchoolAi\SchoolAiDoctrineExecutor;
use App\Service\SchoolAi\SchoolAiPlanParser;
use App\Service\SchoolAi\SchoolAiSchemaCatalog;
use App\Service\SchoolAi\SchoolAiUniversalExporter;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/assistant-ia-universel')]
class SchoolAiUniversalAssistantController extends AbstractController
{
    private const FORMATS = ['auto', 'html', 'pdf', 'xlsx', 'docx'];
    private const MAX_QUESTION_LENGTH = 2000;
    private const MAX_REQUESTS_PER_MINUTE = 20;

    #[Route('', name: 'school_ai_universal_index', methods: ['GET'])]
    public function index(
        Request $request,
        SchoolAiSchemaCatalog $catalog,
        SchoolAiPlanParser $parser,
        SchoolYearRepository $schoolYearRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $schoolYear = $this->selectedSchoolYear($request, $schoolYearRepository);

        return $this->render('school_ai_universal/index.html.twig', [
            'schemaCount' => count($catalog->getCatalog()),
            'selectedSchoolYear' => $schoolYear->getSchoolYear(),
            'aiConfigured' => $parser->isAiConfigured(),
        ]);
    }

    #[Route('/ask', name: 'school_ai_universal_ask', methods: ['POST'])]
    public function ask(
        Request $request,
        SchoolAiPlanParser $parser,
        SchoolAiDoctrineExecutor $executor,
        SchoolAiAnswerFormatter $formatter,
        SchoolAiUniversalExporter $exporter,
        SchoolYearRepository $schoolYearRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isCsrfTokenValid('school_ai_ask', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'La session de sécurité a expiré. Rechargez la page puis réessayez.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$this->consumeRateLimit($request)) {
            $response = $this->jsonResponse([
                'success' => false,
                'message' => 'Trop de questions ont été envoyées. Patientez quelques secondes.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', '20');

            return $response;
        }

        try {
            $payload = str_contains((string) $request->headers->get('Content-Type'), 'application/json')
                ? $request->toArray()
                : $request->request->all();
        } catch (\Throwable) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'La requête envoyée est invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $question = trim((string) ($payload['question'] ?? ''));
        $format = strtolower(trim((string) ($payload['format'] ?? 'auto')));

        if ($question === '') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Veuillez saisir une question.',
            ], Response::HTTP_BAD_REQUEST);
        }
        if (mb_strlen($question) > self::MAX_QUESTION_LENGTH) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'La question est trop longue. Limite : 2 000 caractères.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!in_array($format, self::FORMATS, true)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => "Le format d'export demandé n'est pas autorisé.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $history = (new AiQueryHistory())->setQuestion($question);
        if ($this->getUser()) {
            $history->setCreatedBy($this->getUser());
        }

        try {
            $schoolYear = $this->selectedSchoolYear($request, $schoolYearRepository);
            $forcedFormat = $format !== 'auto' ? $format : null;
            $plan = $parser->parse($question, $forcedFormat, $schoolYear->getSchoolYear());
            $execution = $executor->execute($plan, $schoolYear);
            $formatted = $formatter->format($execution);
            $filename = $exporter->export($formatted, (string) ($plan['format'] ?? 'html'));
            $downloadUrl = null;

            if ($filename !== null) {
                $this->authorizeExportForSession($request, $filename);
                $downloadUrl = $this->generateUrl('school_ai_universal_download', [
                    'filename' => $filename,
                ]);
            }

            $history
                ->setStatus('success')
                ->setOutputFormat((string) ($plan['format'] ?? 'html'))
                ->setQueryPlan($plan)
                ->setAnswer((string) ($formatted['summary'] ?? ''))
                ->setResultPreview(array_slice($formatted['rows'] ?? [], 0, 5))
                ->setRowCount((int) ($execution['rowCount'] ?? 0))
                ->setFilePath($filename);
            $this->saveHistoryWithoutBlocking($em, $history);

            return $this->jsonResponse([
                'success' => true,
                'answer' => $formatted,
                'rowCount' => (int) ($execution['rowCount'] ?? 0),
                'file' => $downloadUrl,
                'fileName' => $filename !== null ? $exporter->downloadName($filename) : null,
                'schoolYear' => $schoolYear->getSchoolYear(),
            ]);
        } catch (\Throwable $exception) {
            $message = $this->safeErrorMessage($exception);
            $history
                ->setStatus('error')
                ->setOutputFormat($format === 'auto' ? 'html' : $format)
                ->setAnswer($message);
            $this->saveHistoryWithoutBlocking($em, $history);

            return $this->jsonResponse([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route(
        '/download/{filename}',
        name: 'school_ai_universal_download',
        requirements: [
            'filename' => 'assistant_ia_\d{8}_\d{6}_[a-f0-9]{32}\.(?:pdf|xlsx|docx)',
        ],
        methods: ['GET']
    )]
    public function download(
        string $filename,
        Request $request,
        SchoolAiUniversalExporter $exporter
    ): BinaryFileResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isExportAuthorizedForSession($request, $filename)) {
            throw $this->createAccessDeniedException("Ce fichier n'appartient pas à votre session.");
        }

        $path = $exporter->resolveFile($filename);
        if ($path === null) {
            throw $this->createNotFoundException('Le fichier a expiré ou est introuvable.');
        }

        $response = $this->file(
            $path,
            $exporter->downloadName($filename),
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function selectedSchoolYear(
        Request $request,
        SchoolYearRepository $schoolYearRepository
    ): SchoolYear {
        $sessionValue = $request->getSession()->get('schoolYear');
        $id = $sessionValue instanceof SchoolYear
            ? $sessionValue->getId()
            : (is_numeric($sessionValue) ? (int) $sessionValue : null);
        $schoolYear = $id ? $schoolYearRepository->find($id) : null;

        if (!$schoolYear instanceof SchoolYear) {
            throw new RuntimeException(
                "Aucune année scolaire active n'a été trouvée. Reconnectez-vous en choisissant une année."
            );
        }

        return $schoolYear;
    }

    private function consumeRateLimit(Request $request): bool
    {
        $now = time();
        $timestamps = $request->getSession()->get('school_ai_request_times', []);
        $timestamps = is_array($timestamps) ? $timestamps : [];
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn ($timestamp) => is_int($timestamp) && $timestamp > $now - 60
        ));

        if (count($timestamps) >= self::MAX_REQUESTS_PER_MINUTE) {
            return false;
        }

        $timestamps[] = $now;
        $request->getSession()->set('school_ai_request_times', $timestamps);

        return true;
    }

    private function authorizeExportForSession(Request $request, string $filename): void
    {
        $exports = $request->getSession()->get('school_ai_exports', []);
        $exports = is_array($exports) ? $exports : [];
        $cutoff = time() - 86400;
        $exports = array_filter(
            $exports,
            static fn ($timestamp) => is_int($timestamp) && $timestamp > $cutoff
        );
        $exports[$filename] = time();
        $request->getSession()->set('school_ai_exports', $exports);
    }

    private function isExportAuthorizedForSession(Request $request, string $filename): bool
    {
        $exports = $request->getSession()->get('school_ai_exports', []);

        return is_array($exports)
            && isset($exports[$filename])
            && is_int($exports[$filename])
            && $exports[$filename] > time() - 86400;
    }

    private function saveHistoryWithoutBlocking(EntityManagerInterface $em, AiQueryHistory $history): void
    {
        try {
            if ($em->isOpen()) {
                $em->persist($history);
                $em->flush();
            }
        } catch (\Throwable) {
            // L'historique ne doit jamais rendre l'assistant inutilisable.
        }
    }

    private function safeErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof RuntimeException) {
            $message = trim($exception->getMessage());
            if ($message !== '') {
                $projectDirectory = (string) $this->getParameter('kernel.project_dir');
                $message = str_replace($projectDirectory, '[application]', $message);

                return mb_substr($message, 0, 400);
            }
        }

        return "La question n'a pas pu être traitée. Reformulez-la plus précisément.";
    }

    private function jsonResponse(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = $this->json($data, $status);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
