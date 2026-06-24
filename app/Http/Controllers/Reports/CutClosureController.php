<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cut;
use App\Models\Company;
use App\Models\ServiceRequest;
use App\Services\ObligationReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CutClosureController extends Controller
{
    public function __construct(
        private readonly ObligationReportService $reportService,
    ) {}

    /**
     * Main closure page: validation + report + export in one view.
     */
    public function show(Cut $cut): View
    {
        $currentCompanyId = (int) session('current_company_id');
        $currentCompany = $currentCompanyId
            ? Company::with('activeContract')->find($currentCompanyId)
            : null;

        if ($currentCompanyId && $cut->contract && (int) $cut->contract->company_id !== $currentCompanyId) {
            abort(403);
        }

        $contractId = (int) $cut->contract_id;

        // Step 1: Validation
        $validation = $this->reportService->validateReadiness($cut, $contractId, $currentCompanyId);

        // Step 2: Obligation report
        $report = $this->reportService->generateReport($cut, $contractId);

        return view('reports.cuts.closure', compact('cut', 'validation', 'report', 'currentCompany'));
    }

    /**
     * Fix orphans: assign all detected orphan requests to this cut.
     */
    public function fixOrphans(Cut $cut, Request $request)
    {
        $currentCompanyId = (int) session('current_company_id');
        $contractId = (int) $cut->contract_id;

        $orphans = $this->reportService->detectOrphans($cut, $contractId, $currentCompanyId);

        if ($orphans->isEmpty()) {
            return back()->with('success', 'No hay solicitudes huérfanas.');
        }

        $ids = $orphans->pluck('id')->all();

        // Remove from other cuts of same contract
        $siblingCutIds = Cut::where('contract_id', $contractId)
            ->where('id', '!=', $cut->id)
            ->pluck('id');

        if ($siblingCutIds->isNotEmpty()) {
            DB::table('cut_service_request')
                ->whereIn('cut_id', $siblingCutIds)
                ->whereIn('service_request_id', $ids)
                ->delete();
        }

        // Assign to this cut
        $pivotData = collect($ids)->map(fn($id) => [
            'cut_id' => $cut->id,
            'service_request_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::table('cut_service_request')
            ->whereIn('service_request_id', $ids)
            ->delete();

        DB::table('cut_service_request')->insert($pivotData);

        return back()->with('success', "{$orphans->count()} solicitud(es) asignada(s) al corte.");
    }

    /**
     * Package evidences into a ZIP organized by family.
     */
    public function packageEvidences(Cut $cut)
    {
        $contractId = (int) $cut->contract_id;
        $report = $this->reportService->generateReport($cut, $contractId);

        $baseFileName = 'corte-' . $cut->id . '-evidencias-' . now()->format('Ymd-His');
        $buildDir = storage_path("app/temp/{$baseFileName}");

        if (is_dir($buildDir)) {
            $this->cleanDirectory($buildDir);
        }
        mkdir($buildDir, 0755, true);

        $totalFiles = 0;

        foreach ($report['obligations'] as $obligation) {
            if ($obligation['request_count'] === 0) continue;

            $folderName = $obligation['number'] > 0
                ? "{$obligation['number']} - " . Str::limit($obligation['family_name'], 80, '')
                : $obligation['family_name'];
            $folderName = preg_replace('/[\\\\\/:"*?<>|]+/', '-', $folderName);

            $familyDir = $buildDir . DIRECTORY_SEPARATOR . $folderName;
            mkdir($familyDir, 0755, true);

            $links = [];

            foreach ($obligation['requests'] as $sr) {
                if (!$sr->relationLoaded('evidences')) {
                    $sr->load('evidences');
                }

                foreach ($sr->evidences as $ev) {
                    if ($ev->evidence_type === 'ARCHIVO' && $ev->file_path) {
                        $storagePath = ltrim(str_replace('storage/', '', $ev->file_path), '/');
                        if (Storage::disk('public')->exists($storagePath)) {
                            $ticketSlug = preg_replace('/[^A-Za-z0-9_-]/', '-', $sr->ticket_number);
                            $ticketDir = $familyDir . DIRECTORY_SEPARATOR . $ticketSlug;
                            if (!is_dir($ticketDir)) mkdir($ticketDir, 0755, true);

                            $fileName = $ev->file_original_name ?: basename($storagePath);
                            $dest = $ticketDir . DIRECTORY_SEPARATOR . $fileName;
                            if (file_exists($dest)) {
                                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                                $base = pathinfo($fileName, PATHINFO_FILENAME);
                                $fileName = "{$base}_{$ev->id}.{$ext}";
                                $dest = $ticketDir . DIRECTORY_SEPARATOR . $fileName;
                            }
                            file_put_contents($dest, Storage::disk('public')->get($storagePath));
                            $totalFiles++;
                        }
                    }
                    if ($ev->evidence_type === 'ENLACE') {
                        $url = $ev->description ?? ($ev->evidence_data['url'] ?? 'N/A');
                        $links[] = "{$sr->ticket_number} | {$ev->title} | {$url}";
                    }
                }
            }

            if (!empty($links)) {
                file_put_contents($familyDir . DIRECTORY_SEPARATOR . '_enlaces.txt', implode("\r\n", $links));
            }
        }

        // Compress
        $zipPath = storage_path("app/temp/{$baseFileName}.zip");
        $escapedBuildDir = str_replace('/', '\\', $buildDir);
        $escapedZipPath = str_replace('/', '\\', $zipPath);

        $psCmd = "Compress-Archive -Path '{$escapedBuildDir}\\*' -DestinationPath '{$escapedZipPath}' -Force";
        exec("powershell.exe -NoProfile -Command \"{$psCmd}\"", $output, $returnCode);

        $this->cleanDirectory($buildDir);

        if ($returnCode !== 0 || !file_exists($zipPath)) {
            return back()->with('error', 'No se pudo generar el archivo ZIP.');
        }

        return response()->download($zipPath, $baseFileName . '.zip')->deleteFileAfterSend();
    }

    /**
     * Export obligation report as copyable HTML table.
     */
    public function exportTable(Cut $cut)
    {
        $contractId = (int) $cut->contract_id;
        $report = $this->reportService->generateReport($cut, $contractId);

        return view('reports.cuts.closure-export', compact('cut', 'report'));
    }

    private function cleanDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }
}
