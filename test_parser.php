<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate session
session(['current_company_id' => 2]);

$text = <<<'EOT'
Plataforma Circulaturas del Sistema Nacional de Circulación
Consuelo del Pilar Salas Leguizamon; Christian Camilo Tiria Buitrago; Pedro Camilo Vargas Sanchez; Piedad Cecilia Montero Villegas; Mary Sol Ramírez Calderón; Daniel Sanchez Sanchez; y 5 más

Mar 9/06/2026, de 11:00 AM a 12:00 PM
Reunión de Microsoft Teams
De: Consuelo del Pilar Salas Leguizamon <csalas@mincultura.gov.co> Enviados: lunes, 25 de mayo de 2026 11:18:28 a. m. (UTC-05:00) Bogota, Lima, Quito, Rio Branco Para: Consuelo del Pilar Salas Leguizamon <csalas@mincultura.gov.co>; Christian Camilo Tiria Buitrago <ctiria@mincultura.gov.co>; Pedro Camilo Vargas Sanchez <pcvargas@mincultura.gov.co>; Piedad Cecilia Montero Villegas <pmontero@mincultura.gov.co>; Mary Sol Ramírez Calderón <mramirezc@mincultura.gov.co>; Daniel Sanchez Sanchez <dsanchezs@mincultura.gov.co>; Wilmer Gustavo Mogollon Duque <wmogollon@mincultura.gov.co>; Daniela Maria Vanegas Angel <dvanegas@mincultura.gov.co>; Lizeth Andrea Lara Posada <llara@mincultura.gov.co> Cc: Leydi Tatiana Escudero Tobar <lescudero@mincultura.gov.co> Asunto: Plataforma Circulaturas del Sistema Nacional de Circulación Cuándo: martes, 9 de junio de 2026 11:00 a. m.-12:00 p. m.. Donde: Reunión de Microsoft Teams
Reunión de Microsoft Teams
Unirse: https://teams.microsoft.com/meet/21227171870827?p=8IkpZa6OzcSYjBI9FY
Id. de reunión: 212 271 718 708 27
Código de acceso: hD9qe2aE
Si la solicitud contenida en el presente mensaje se recibe por fuera del horario laboral, se recomienda su lectura y trámite dentro del horario laboral del destinatario.
EOT;

echo "LLM_ENABLED: " . (config('services.llm.enabled') ? 'true' : 'false') . "\n";
echo "Parsing text of length: " . strlen($text) . "\n";
echo "Starting...\n";
flush();

$start = microtime(true);

try {
    $service = app(\App\Services\ServiceRequestPlainTextImportService::class);
    $result = $service->parseToFormData($text, 2, 3);
    $elapsed = round(microtime(true) - $start, 2);
    echo "SUCCESS in {$elapsed}s\n";
    echo "Title: " . ($result['payload']['title'] ?? 'N/A') . "\n";
    echo "Sub-service: " . ($result['payload']['sub_service_id'] ?? 'N/A') . "\n";
    echo "Requester: " . ($result['meta']['requester_name'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    $elapsed = round(microtime(true) - $start, 2);
    echo "ERROR in {$elapsed}s\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
}
