<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class EvidenceActaValidationTest extends TestCase
{
    /**
     * Valid MIME types for ACTA evidence.
     */
    private array $validActaMimeTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ];

    /**
     * Test that ACTA evidence type is accepted as valid.
     */
    public function test_acta_is_valid_evidence_type(): void
    {
        $rules = [
            'evidence_type' => 'required|string|in:PASO_A_PASO,ARCHIVO,COMENTARIO,ENLACE,ACTA',
        ];

        $validator = Validator::make(['evidence_type' => 'ACTA'], $rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that invalid evidence types are rejected.
     */
    public function test_invalid_evidence_type_is_rejected(): void
    {
        $rules = [
            'evidence_type' => 'required|string|in:PASO_A_PASO,ARCHIVO,COMENTARIO,ENLACE,ACTA',
        ];

        $validator = Validator::make(['evidence_type' => 'INVALID_TYPE'], $rules);

        $this->assertTrue($validator->fails());
    }

    /**
     * Test that PDF files are accepted for ACTA evidence.
     */
    public function test_pdf_file_accepted_for_acta(): void
    {
        $file = UploadedFile::fake()->create('minutes.pdf', 100, 'application/pdf');

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that DOCX files are accepted for ACTA evidence.
     */
    public function test_docx_file_accepted_for_acta(): void
    {
        $file = UploadedFile::fake()->create(
            'minutes.docx',
            100,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that JPEG files are accepted for ACTA evidence.
     */
    public function test_jpeg_file_accepted_for_acta(): void
    {
        $file = UploadedFile::fake()->image('minutes.jpg');

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that PNG files are accepted for ACTA evidence.
     */
    public function test_png_file_accepted_for_acta(): void
    {
        $file = UploadedFile::fake()->image('minutes.png');

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * Test that Excel files are rejected for ACTA evidence.
     */
    public function test_excel_file_rejected_for_acta(): void
    {
        $file = UploadedFile::fake()->create(
            'spreadsheet.xlsx',
            100,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->fails());
    }

    /**
     * Test that text files are rejected for ACTA evidence.
     */
    public function test_text_file_rejected_for_acta(): void
    {
        $file = UploadedFile::fake()->create('notes.txt', 100, 'text/plain');

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->fails());
    }

    /**
     * Test that zip files are rejected for ACTA evidence.
     */
    public function test_zip_file_rejected_for_acta(): void
    {
        $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

        $rules = [
            'files.*' => 'required|file|max:10240|mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/jpg,image/png',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->fails());
    }

    /**
     * Test that non-ACTA uploads allow broader file types.
     */
    public function test_non_acta_uploads_allow_broader_file_types(): void
    {
        $file = UploadedFile::fake()->create('spreadsheet.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Standard ARCHIVO rules allow xlsx
        $rules = [
            'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar,csv,svg',
        ];

        $validator = Validator::make(['files' => [$file]], $rules);

        $this->assertTrue($validator->passes());
    }
}
