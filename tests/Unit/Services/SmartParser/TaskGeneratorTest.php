<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\Extractors\TaskGenerator;
use App\Services\SmartParser\ValueObjects\ExtractionResult;
use App\Services\SmartParser\ValueObjects\ParsingContext;
use PHPUnit\Framework\TestCase;

class TaskGeneratorTest extends TestCase
{
    private TaskGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new TaskGenerator();
    }

    // --- List detection with bullet points ---

    public function test_detects_asterisk_bullet_list(): void
    {
        $context = $this->makeContext("Tareas pendientes:\n* Revisar el diseño\n* Actualizar la base de datos\n* Enviar reporte final");
        $result = $this->generator->extract($context);

        $this->assertEquals('tasks', $result->fieldName);
        $this->assertCount(1, $result->value);
        $this->assertCount(3, $result->value[0]['subtasks']);
        $this->assertEquals('Revisar el diseño', $result->value[0]['subtasks'][0]['title']);
        $this->assertEquals('Actualizar la base de datos', $result->value[0]['subtasks'][1]['title']);
        $this->assertEquals('Enviar reporte final', $result->value[0]['subtasks'][2]['title']);
    }

    public function test_detects_dash_bullet_list(): void
    {
        $context = $this->makeContext("Acciones requeridas:\n- Configurar servidor\n- Instalar dependencias\n- Ejecutar migraciones");
        $result = $this->generator->extract($context);

        $this->assertCount(3, $result->value[0]['subtasks']);
        $this->assertEquals('Configurar servidor', $result->value[0]['subtasks'][0]['title']);
    }

    public function test_detects_bullet_point_character_list(): void
    {
        $context = $this->makeContext("Pasos a seguir:\n• Primer paso del proceso\n• Segundo paso del proceso\n• Tercer paso del proceso");
        $result = $this->generator->extract($context);

        $this->assertCount(3, $result->value[0]['subtasks']);
        $this->assertEquals('Primer paso del proceso', $result->value[0]['subtasks'][0]['title']);
    }

    // --- List detection with numbering ---

    public function test_detects_numeric_dot_list(): void
    {
        $context = $this->makeContext("Plan de trabajo:\n1. Analizar requerimientos\n2. Diseñar solución\n3. Implementar cambios");
        $result = $this->generator->extract($context);

        $this->assertCount(3, $result->value[0]['subtasks']);
        $this->assertEquals('Analizar requerimientos', $result->value[0]['subtasks'][0]['title']);
        $this->assertEquals('Diseñar solución', $result->value[0]['subtasks'][1]['title']);
        $this->assertEquals('Implementar cambios', $result->value[0]['subtasks'][2]['title']);
    }

    public function test_detects_numeric_parenthesis_list(): void
    {
        $context = $this->makeContext("Actividades:\n1) Revisar documentación\n2) Preparar ambiente\n3) Ejecutar pruebas");
        $result = $this->generator->extract($context);

        $this->assertCount(3, $result->value[0]['subtasks']);
        $this->assertEquals('Revisar documentación', $result->value[0]['subtasks'][0]['title']);
    }

    public function test_detects_alpha_parenthesis_list(): void
    {
        $context = $this->makeContext("Opciones disponibles:\na) Revisar el código fuente\nb) Actualizar las dependencias\nc) Desplegar en producción");
        $result = $this->generator->extract($context);

        $this->assertCount(3, $result->value[0]['subtasks']);
        $this->assertEquals('Revisar el código fuente', $result->value[0]['subtasks'][0]['title']);
    }

    public function test_detects_alpha_dot_list(): void
    {
        $context = $this->makeContext("Tareas asignadas:\na. Primera tarea asignada\nb. Segunda tarea asignada");
        $result = $this->generator->extract($context);

        $this->assertCount(2, $result->value[0]['subtasks']);
        $this->assertEquals('Primera tarea asignada', $result->value[0]['subtasks'][0]['title']);
    }

    // --- Subtask validation (3-255 chars) ---

    public function test_discards_items_shorter_than_3_chars(): void
    {
        $context = $this->makeContext("Lista:\n* AB\n* Tarea válida con texto suficiente\n* XY");
        $result = $this->generator->extract($context);

        $this->assertCount(1, $result->value[0]['subtasks']);
        $this->assertEquals('Tarea válida con texto suficiente', $result->value[0]['subtasks'][0]['title']);
    }

    public function test_discards_items_longer_than_255_chars(): void
    {
        $longText = str_repeat('a', 256);
        $context = $this->makeContext("Lista:\n* {$longText}\n* Tarea válida con texto");
        $result = $this->generator->extract($context);

        $this->assertCount(1, $result->value[0]['subtasks']);
        $this->assertEquals('Tarea válida con texto', $result->value[0]['subtasks'][0]['title']);
    }

    public function test_accepts_items_with_exactly_3_chars(): void
    {
        $context = $this->makeContext("Lista:\n* ABC\n* Otra tarea válida");
        $result = $this->generator->extract($context);

        $this->assertCount(2, $result->value[0]['subtasks']);
        $this->assertEquals('ABC', $result->value[0]['subtasks'][0]['title']);
    }

    public function test_accepts_items_with_exactly_255_chars(): void
    {
        $text255 = str_repeat('a', 255);
        $context = $this->makeContext("Lista:\n* {$text255}\n* Otra tarea");
        $result = $this->generator->extract($context);

        $this->assertCount(2, $result->value[0]['subtasks']);
        $this->assertEquals($text255, $result->value[0]['subtasks'][0]['title']);
    }

    // --- Maximum 20 subtasks ---

    public function test_limits_subtasks_to_maximum_20(): void
    {
        $lines = "Tareas:\n";
        for ($i = 1; $i <= 25; $i++) {
            $lines .= "* Tarea número {$i} del listado\n";
        }
        $context = $this->makeContext($lines);
        $result = $this->generator->extract($context);

        $this->assertCount(20, $result->value[0]['subtasks']);
    }

    // --- Duration detection ---

    public function test_detects_duration_in_minutos(): void
    {
        $context = $this->makeContext("Tareas:\n* Revisar código 30 minutos\n* Escribir tests 45 minutos");
        $result = $this->generator->extract($context);

        $this->assertEquals(30, $result->value[0]['subtasks'][0]['estimated_minutes']);
        $this->assertEquals(45, $result->value[0]['subtasks'][1]['estimated_minutes']);
    }

    public function test_detects_duration_in_horas(): void
    {
        $context = $this->makeContext("Tareas:\n* Reunión de planificación 2 horas\n* Desarrollo de feature 3 horas");
        $result = $this->generator->extract($context);

        $this->assertEquals(120, $result->value[0]['subtasks'][0]['estimated_minutes']);
        $this->assertEquals(180, $result->value[0]['subtasks'][1]['estimated_minutes']);
    }

    public function test_detects_duration_in_min_abbreviation(): void
    {
        $context = $this->makeContext("Tareas:\n* Revisión rápida 15 min\n* Ajuste de estilos 20 min");
        $result = $this->generator->extract($context);

        $this->assertEquals(15, $result->value[0]['subtasks'][0]['estimated_minutes']);
        $this->assertEquals(20, $result->value[0]['subtasks'][1]['estimated_minutes']);
    }

    public function test_detects_duration_in_h_abbreviation(): void
    {
        $context = $this->makeContext("Tareas:\n* Desarrollo completo 1h\n* Testing integral 2h");
        $result = $this->generator->extract($context);

        $this->assertEquals(60, $result->value[0]['subtasks'][0]['estimated_minutes']);
        $this->assertEquals(120, $result->value[0]['subtasks'][1]['estimated_minutes']);
    }

    public function test_detects_duration_in_hrs_abbreviation(): void
    {
        $context = $this->makeContext("Tareas:\n* Análisis de datos 2 hrs\n* Documentación 1 hr");
        $result = $this->generator->extract($context);

        $this->assertEquals(120, $result->value[0]['subtasks'][0]['estimated_minutes']);
        $this->assertEquals(60, $result->value[0]['subtasks'][1]['estimated_minutes']);
    }

    // --- Duration clamping ---

    public function test_clamps_duration_minimum_to_5_minutes(): void
    {
        $context = $this->makeContext("Tareas:\n* Tarea muy rápida 2 minutos");
        $result = $this->generator->extract($context);

        $this->assertEquals(5, $result->value[0]['subtasks'][0]['estimated_minutes']);
    }

    public function test_clamps_duration_maximum_to_480_minutes(): void
    {
        $context = $this->makeContext("Tareas:\n* Tarea muy larga 10 horas");
        $result = $this->generator->extract($context);

        $this->assertEquals(480, $result->value[0]['subtasks'][0]['estimated_minutes']);
    }

    // --- Default duration ---

    public function test_assigns_25_minutes_default_when_no_duration(): void
    {
        $context = $this->makeContext("Tareas:\n* Revisar el diseño del sistema\n* Actualizar documentación");
        $result = $this->generator->extract($context);

        $this->assertEquals(25, $result->value[0]['subtasks'][0]['estimated_minutes']);
        $this->assertEquals(25, $result->value[0]['subtasks'][1]['estimated_minutes']);
    }

    // --- estimated_hours calculation ---

    public function test_calculates_estimated_hours_as_sum_divided_by_60(): void
    {
        $context = $this->makeContext("Tareas:\n* Tarea uno 30 minutos\n* Tarea dos 60 minutos\n* Tarea tres 30 minutos");
        $result = $this->generator->extract($context);

        // 30 + 60 + 30 = 120 minutes = 2.0 hours
        $this->assertEquals(2.0, $result->value[0]['estimated_hours']);
    }

    public function test_calculates_estimated_hours_with_default_durations(): void
    {
        $context = $this->makeContext("Tareas:\n* Primera tarea sin duración\n* Segunda tarea sin duración");
        $result = $this->generator->extract($context);

        // 25 + 25 = 50 minutes = 0.83 hours
        $this->assertEquals(round(50 / 60, 2), $result->value[0]['estimated_hours']);
    }

    // --- Single task generation (no list) ---

    public function test_generates_single_task_when_no_list_found(): void
    {
        $context = $this->makeContext('Necesito que actualicen el contenido de la página de contacto con la nueva dirección de la empresa.');
        $result = $this->generator->extract($context);

        $this->assertEquals('tasks', $result->fieldName);
        $this->assertCount(1, $result->value);
        $this->assertEmpty($result->value[0]['subtasks']);
        $this->assertEquals(25, $result->value[0]['estimated_minutes']);
    }

    public function test_single_task_uses_subject_as_title_when_available(): void
    {
        $context = $this->makeContext('Necesito actualizar la página de contacto');
        $context->emailHeaders = ['Asunto' => 'Actualización de página de contacto'];
        $result = $this->generator->extract($context);

        $this->assertEquals('Actualización de página de contacto', $result->value[0]['title']);
    }

    public function test_single_task_uses_first_sentence_when_no_subject(): void
    {
        $context = $this->makeContext("Hola equipo\nNecesito que revisen el módulo de reportes porque tiene errores.");
        $result = $this->generator->extract($context);

        $this->assertEquals('Necesito que revisen el módulo de reportes porque tiene errores.', $result->value[0]['title']);
    }

    // --- Task structure ---

    public function test_task_has_correct_structure(): void
    {
        $context = $this->makeContext("Tareas:\n* Revisar el código del módulo");
        $result = $this->generator->extract($context);

        $task = $result->value[0];
        $this->assertArrayHasKey('title', $task);
        $this->assertArrayHasKey('description', $task);
        $this->assertArrayHasKey('type', $task);
        $this->assertArrayHasKey('priority', $task);
        $this->assertArrayHasKey('estimated_minutes', $task);
        $this->assertArrayHasKey('estimated_hours', $task);
        $this->assertArrayHasKey('subtasks', $task);

        $this->assertNull($task['description']);
        $this->assertEquals('regular', $task['type']);
        $this->assertEquals('medium', $task['priority']);
    }

    public function test_subtask_has_correct_structure(): void
    {
        $context = $this->makeContext("Tareas:\n* Revisar el código del módulo");
        $result = $this->generator->extract($context);

        $subtask = $result->value[0]['subtasks'][0];
        $this->assertArrayHasKey('title', $subtask);
        $this->assertArrayHasKey('priority', $subtask);
        $this->assertArrayHasKey('estimated_minutes', $subtask);
        $this->assertEquals('medium', $subtask['priority']);
    }

    // --- ExtractionResult structure ---

    public function test_result_has_correct_field_name(): void
    {
        $context = $this->makeContext('Texto simple sin lista de acciones');
        $result = $this->generator->extract($context);

        $this->assertInstanceOf(ExtractionResult::class, $result);
        $this->assertEquals('tasks', $result->fieldName);
        $this->assertTrue($result->extracted);
    }

    public function test_result_with_list_has_higher_confidence(): void
    {
        $context = $this->makeContext("Tareas:\n* Revisar código fuente\n* Actualizar tests");
        $result = $this->generator->extract($context);

        $this->assertEquals(85, $result->confidence);
    }

    public function test_result_without_list_has_lower_confidence(): void
    {
        $context = $this->makeContext('Necesito actualizar la página de contacto con nueva información');
        $result = $this->generator->extract($context);

        $this->assertEquals(60, $result->confidence);
    }

    // --- Helper ---

    private function makeContext(string $text): ParsingContext
    {
        $context = new ParsingContext();
        $context->rawText = $text;
        $context->normalizedText = $text;
        $context->companyId = 1;
        $context->contractId = 1;

        return $context;
    }
}
