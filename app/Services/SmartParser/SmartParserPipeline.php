<?php

declare(strict_types=1);

namespace App\Services\SmartParser;

use App\Models\Company;
use App\Services\SmartParser\Extractors\ChannelDetector;
use App\Services\SmartParser\Extractors\CriticalityDetector;
use App\Services\SmartParser\Extractors\DateExtractor;
use App\Services\SmartParser\Extractors\RequesterExtractor;
use App\Services\SmartParser\Extractors\SubServiceClassifier;
use App\Services\SmartParser\Extractors\TaskGenerator;
use App\Services\SmartParser\Extractors\TitleDescriptionExtractor;
use App\Services\SmartParser\Extractors\UrlExtractor;
use App\Services\SmartParser\Resolvers\RequesterResolver;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsedResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use App\Services\SmartParser\Exceptions\ParsingTimeoutException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmartParserPipeline
{
    /**
     * Maximum allowed time in seconds for the pipeline execution.
     */
    private const TIMEOUT_SECONDS = 20;

    private ?float $startTime = null;

    public function __construct(
        private readonly TextNormalizer $normalizer,
        private readonly ChannelDetector $channelDetector,
        private readonly RequesterExtractor $requesterExtractor,
        private readonly TitleDescriptionExtractor $titleDescriptionExtractor,
        private readonly SubServiceClassifier $subServiceClassifier,
        private readonly DateExtractor $dateExtractor,
        private readonly UrlExtractor $urlExtractor,
        private readonly CriticalityDetector $criticalityDetector,
        private readonly TaskGenerator $taskGenerator,
        private readonly RequesterResolver $requesterResolver,
    ) {}

    /**
     * Ejecuta el pipeline completo de extracción sobre texto libre.
     *
     * @throws ParsingTimeoutException
     */
    public function parse(string $rawText, int $companyId): ParsedResult
    {
        $this->startTime = microtime(true);

        // 1. Create the parsing context
        $context = new ParsingContext();
        $context->rawText = $rawText;
        $context->companyId = $companyId;

        // 2. Resolve the contractId from the company's active contract
        $context->contractId = $this->resolveContractId($companyId);

        // 3. Normalize the text
        $context->normalizedText = $this->normalizer->normalize($rawText);

        // 4. Run extractors sequentially, collecting results and confidences
        $confidences = [];
        $results = [];

        // --- Channel Detection ---
        $channelResult = $this->runExtractor('channel', fn () => $this->channelDetector->extract($context));
        $results['channel'] = $channelResult;
        if ($channelResult->extracted) {
            $context->detectedChannel = $channelResult->value;
            $confidences['channel'] = $channelResult->confidence;
        }

        // --- Requester Extraction ---
        $requesterResult = $this->runExtractor('requester', fn () => $this->requesterExtractor->extract($context));
        $results['requester'] = $requesterResult;
        $resolvedRequester = null;

        if ($requesterResult->extracted && $requesterResult->value !== null) {
            $name = $requesterResult->value['name'] ?? '';
            $email = $requesterResult->value['email'] ?? null;

            if ($name !== '') {
                $resolvedRequester = $this->requesterResolver->resolve($companyId, $name, $email);
            }

            $confidences['requester'] = $requesterResult->confidence;
        }

        // --- Title/Description Extraction ---
        $titleDescResult = $this->runExtractor('title_description', fn () => $this->titleDescriptionExtractor->extract($context));
        $results['title_description'] = $titleDescResult;
        if ($titleDescResult->extracted && $titleDescResult->value !== null) {
            // Set messageBody in context for downstream extractors
            if ($context->messageBody === null && isset($titleDescResult->value['description'])) {
                $context->messageBody = $titleDescResult->value['description'];
            }
            $confidences['title'] = $titleDescResult->confidence;
            $confidences['description'] = $titleDescResult->confidence;
        }

        // --- SubService Classification ---
        $subServiceResult = $this->runExtractor('sub_service', fn () => $this->subServiceClassifier->extract($context));
        $results['sub_service'] = $subServiceResult;
        if ($subServiceResult->extracted && $subServiceResult->value !== null) {
            $confidences['sub_service'] = $subServiceResult->confidence;
        }

        // --- Date Extraction ---
        $dateResult = $this->runExtractor('dates', fn () => $this->dateExtractor->extract($context));
        $results['dates'] = $dateResult;
        if ($dateResult->extracted && $dateResult->value !== null) {
            $confidences['dates'] = $dateResult->confidence;
        }

        // --- URL Extraction ---
        $urlResult = $this->runExtractor('web_routes', fn () => $this->urlExtractor->extract($context));
        $results['web_routes'] = $urlResult;
        if ($urlResult->extracted && $urlResult->value !== null) {
            $confidences['web_routes'] = $urlResult->confidence;
        }

        // --- Criticality Detection ---
        $criticalityResult = $this->runExtractor('criticality_level', fn () => $this->criticalityDetector->extract($context));
        $results['criticality_level'] = $criticalityResult;
        if ($criticalityResult->extracted) {
            $confidences['criticality_level'] = $criticalityResult->confidence;
        }

        // --- Task Generation ---
        $taskResult = $this->runExtractor('tasks', fn () => $this->taskGenerator->extract($context));
        $results['tasks'] = $taskResult;
        if ($taskResult->extracted && $taskResult->value !== null) {
            $confidences['tasks'] = $taskResult->confidence;
        }

        // 5. Build the ParsedResult from all extraction results
        return $this->buildParsedResult($results, $resolvedRequester, $confidences);
    }

    /**
     * Runs an extractor with fault tolerance. If the extractor throws,
     * the exception is logged and an empty result is returned.
     * ParsingTimeoutException is re-thrown to abort the pipeline.
     */
    private function runExtractor(string $fieldName, callable $extractorFn): ExtractionResult
    {
        $this->checkTimeout();

        try {
            return $extractorFn();
        } catch (ParsingTimeoutException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning("SmartParserPipeline: extractor '{$fieldName}' failed", [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return ExtractionResult::empty($fieldName);
        }
    }

    /**
     * Checks if the pipeline has exceeded the maximum allowed execution time.
     *
     * @throws ParsingTimeoutException
     */
    private function checkTimeout(): void
    {
        if ($this->startTime === null) {
            return;
        }

        $elapsed = microtime(true) - $this->startTime;
        if ($elapsed > self::TIMEOUT_SECONDS) {
            throw new ParsingTimeoutException(self::TIMEOUT_SECONDS);
        }
    }

    /**
     * Resolves the active contract ID for the given company.
     */
    private function resolveContractId(int $companyId): int
    {
        $company = Company::find($companyId);

        if ($company === null || $company->active_contract_id === null) {
            return 0;
        }

        return (int) $company->active_contract_id;
    }

    /**
     * Builds the final ParsedResult from all extraction results.
     *
     * @param array<string, ExtractionResult> $results
     * @param array{id: ?int, name: string, pending: bool, email: ?string}|null $resolvedRequester
     * @param array<string, int> $confidences
     */
    private function buildParsedResult(array $results, ?array $resolvedRequester, array $confidences): ParsedResult
    {
        // Title & Description
        $title = '';
        $description = '';
        if ($results['title_description']->extracted && $results['title_description']->value !== null) {
            $title = $results['title_description']->value['title'] ?? '';
            $description = $results['title_description']->value['description'] ?? '';
        }

        // Channel
        $channel = $results['channel']->extracted
            ? ($results['channel']->value ?? 'email_corporativo')
            : 'email_corporativo';

        // Requester
        $requesterName = '';
        $requesterEmail = null;
        $requesterId = null;
        $requesterPending = false;
        if ($resolvedRequester !== null) {
            $requesterName = $resolvedRequester['name'] ?? '';
            $requesterEmail = $resolvedRequester['email'] ?? null;
            $requesterId = $resolvedRequester['id'] ?? null;
            $requesterPending = $resolvedRequester['pending'] ?? false;
        }

        // SubService
        $subServiceId = null;
        $serviceId = null;
        $familyId = null;
        $slaId = null;
        if ($results['sub_service']->extracted && $results['sub_service']->value !== null) {
            $subServiceId = $results['sub_service']->value['sub_service_id'] ?? null;
            $serviceId = $results['sub_service']->value['service_id'] ?? null;
            $familyId = $results['sub_service']->value['family_id'] ?? null;
            $slaId = $results['sub_service']->value['sla_id'] ?? null;
        }

        // Dates
        $createdAt = null;
        $dueDate = null;
        if ($results['dates']->extracted && $results['dates']->value !== null) {
            $createdAt = $results['dates']->value['created_at'] ?? null;
            $dueDate = $results['dates']->value['due_date'] ?? null;
        }
        if ($createdAt === null) {
            $createdAt = Carbon::now();
        }

        // URLs
        $webRoutes = [];
        if ($results['web_routes']->extracted && $results['web_routes']->value !== null) {
            $webRoutes = $results['web_routes']->value;
        }

        // Criticality
        $criticalityLevel = $results['criticality_level']->extracted
            ? ($results['criticality_level']->value ?? 'MEDIA')
            : 'MEDIA';

        // Tasks
        $tasks = [];
        if ($results['tasks']->extracted && $results['tasks']->value !== null) {
            $tasks = $results['tasks']->value;
        }

        return new ParsedResult(
            title: $title,
            description: $description,
            channel: $channel,
            requesterName: $requesterName,
            requesterEmail: $requesterEmail,
            requesterId: $requesterId,
            requesterPending: $requesterPending,
            subServiceId: $subServiceId,
            serviceId: $serviceId,
            familyId: $familyId,
            slaId: $slaId,
            createdAt: $createdAt,
            dueDate: $dueDate,
            criticalityLevel: $criticalityLevel,
            webRoutes: $webRoutes,
            tasks: $tasks,
            confidences: $confidences,
        );
    }
}
