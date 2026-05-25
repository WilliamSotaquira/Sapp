<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmartParser;

use App\Services\SmartParser\StructuredFormatDetector;
use PHPUnit\Framework\TestCase;

class StructuredFormatDetectorTest extends TestCase
{
    private StructuredFormatDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new StructuredFormatDetector();
    }

    // --- Valid structured format tests ---

    public function test_detects_valid_structured_format_with_all_fields(): void
    {
        $text = implode("\n", [
            'Actualización de contenido en portal web',
            'Se requiere actualizar la sección de noticias del portal institucional con la información del nuevo programa.',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez García',
            'email_corporativo',
            'Actualización de Contenido Web',
            'https://www.ejemplo.com/pagina1',
            'MEDIA',
            'Actividad - Subtareas:',
            '- Revisar contenido actual (30 min)',
            '- Actualizar textos (1h)',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    public function test_detects_structured_format_with_numeric_dates(): void
    {
        $text = implode("\n", [
            'Solicitud de soporte técnico',
            'El equipo de cómputo no enciende desde ayer.',
            '15/05/2025',
            '20/05/2025',
            'María López',
            'telefono',
            'Soporte Técnico General',
            'No disponible',
            'ALTA',
            'Tarea principal - Subtarea:',
            '- Diagnosticar equipo (25 min)',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    public function test_detects_structured_format_with_no_disponible_dates(): void
    {
        $text = implode("\n", [
            'Título de la solicitud',
            'Descripción detallada de la solicitud.',
            'No disponible',
            'No disponible',
            'Carlos Rodríguez',
            'whatsapp',
            'Mantenimiento Preventivo',
            'https://ejemplo.com',
            'BAJA',
            'Actividad con subtareas',
            '- Primera subtarea',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    public function test_detects_structured_format_with_mixed_date_formats(): void
    {
        $text = implode("\n", [
            'Reporte de enlace roto',
            'El enlace de la página principal no funciona.',
            '2025-05-16',
            'No disponible',
            'Ana García',
            'email_digital',
            'Reporte de Enlace Roto o Contenido Obsoleto',
            'https://portal.gov.co/broken-link',
            'URGENTE',
            'Corrección de subtareas del enlace',
            '- Verificar enlace (15 min)',
            '- Corregir URL (10 min)',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    // --- Invalid format tests ---

    public function test_rejects_text_with_fewer_than_10_lines(): void
    {
        $text = implode("\n", [
            'Título',
            'Descripción',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez',
        ]);

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_text_with_invalid_date_on_line_2(): void
    {
        $text = implode("\n", [
            'Título de la solicitud',
            'Descripción de la solicitud.',
            'Esto no es una fecha válida',
            'No disponible',
            'Juan Pérez',
            'email_corporativo',
            'Subservicio',
            'https://ejemplo.com',
            'MEDIA',
            'Actividad con subtareas',
            '- Subtarea 1',
        ]);

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_text_with_invalid_date_on_line_3(): void
    {
        $text = implode("\n", [
            'Título de la solicitud',
            'Descripción de la solicitud.',
            '16 de mayo de 2025',
            'Esto tampoco es una fecha',
            'Juan Pérez',
            'email_corporativo',
            'Subservicio',
            'https://ejemplo.com',
            'MEDIA',
            'Actividad con subtareas',
            '- Subtarea 1',
        ]);

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_text_with_empty_channel_line(): void
    {
        // When there are only 9 non-empty lines (channel field missing entirely),
        // the format should be rejected because it needs at least 10 lines
        $text = implode("\n", [
            'Título',
            'Descripción',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez',
            // Channel line missing - only 9 non-empty lines total
            'Subservicio',
            'https://ejemplo.com',
            'MEDIA',
            'Actividad con subtareas',
        ]);

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_text_without_subtarea_keyword_on_line_9(): void
    {
        $text = implode("\n", [
            'Título de la solicitud',
            'Descripción de la solicitud.',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez',
            'email_corporativo',
            'Subservicio',
            'https://ejemplo.com',
            'MEDIA',
            'Actividad sin la palabra clave',
            '- Tarea 1',
        ]);

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_free_form_email_text(): void
    {
        $text = "De: Juan Pérez <juan@ejemplo.com>\nPara: soporte@empresa.com\nAsunto: Problema con el portal\nFecha: 16 de mayo de 2025\n\nHola equipo,\n\nTengo un problema con el portal web. La página de inicio no carga correctamente.\n\nSaludos,\nJuan";

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_whatsapp_message(): void
    {
        $text = "[16/05/2025, 10:30] Juan Pérez: Hola, necesito ayuda con el sistema\n[16/05/2025, 10:31] Juan Pérez: No puedo acceder al portal\n[16/05/2025, 10:35] Soporte: Revisamos en un momento";

        $this->assertFalse($this->detector->isStructuredFormat($text));
    }

    public function test_rejects_empty_text(): void
    {
        $this->assertFalse($this->detector->isStructuredFormat(''));
    }

    public function test_rejects_short_text(): void
    {
        $this->assertFalse($this->detector->isStructuredFormat('Texto corto'));
    }

    // --- Edge cases ---

    public function test_handles_windows_line_endings(): void
    {
        $text = "Título\r\nDescripción\r\n16 de mayo de 2025\r\nNo disponible\r\nJuan Pérez\r\nemail_corporativo\r\nSubservicio\r\nhttps://ejemplo.com\r\nMEDIA\r\nActividad con subtareas\r\n- Subtarea 1";

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    public function test_handles_markdown_links_in_urls_line(): void
    {
        $text = implode("\n", [
            'Título de la solicitud',
            'Descripción de la solicitud.',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez',
            'email_corporativo',
            'Subservicio',
            '[Portal](https://ejemplo.com/portal)',
            'MEDIA',
            'Actividad con subtareas',
            '- Subtarea 1',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    public function test_detects_subtareas_keyword_case_insensitive(): void
    {
        $text = implode("\n", [
            'Título',
            'Descripción',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez',
            'email_corporativo',
            'Subservicio',
            'https://ejemplo.com',
            'MEDIA',
            'Actividad - SUBTAREAS:',
            '- Subtarea 1',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }

    public function test_detects_singular_subtarea_keyword(): void
    {
        $text = implode("\n", [
            'Título',
            'Descripción',
            '16 de mayo de 2025',
            'No disponible',
            'Juan Pérez',
            'email_corporativo',
            'Subservicio',
            'https://ejemplo.com',
            'MEDIA',
            'Actividad - Subtarea principal',
            '- Paso 1',
        ]);

        $this->assertTrue($this->detector->isStructuredFormat($text));
    }
}
