<?php

namespace Tests\Feature;

use App\Support\MediaFormats;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DuraMediaFormatsTest extends TestCase
{
    public function test_document_field_accepts_pdf_but_photo_field_rejects_it(): void
    {
        $file = UploadedFile::fake()->createWithContent('rc.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $this->assertSame('pdf', MediaFormats::inspect($file, true)['extension']);
        $this->expectException(ValidationException::class);
        MediaFormats::inspect($file, false);
    }

    public function test_changing_extension_does_not_turn_text_into_a_photo(): void
    {
        $this->expectException(ValidationException::class);
        MediaFormats::inspect(UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo "hello";'), false);
    }

    public function test_csv_text_is_preserved_as_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent('payouts.csv', "name,amount\nAlice,500\nBob,800\n");
        $this->assertSame('csv', MediaFormats::inspect($file, true)['extension']);
    }

    public function test_fake_office_zip_is_rejected(): void
    {
        if (!class_exists(\ZipArchive::class)) { $this->markTestSkipped('ZIP extension required.'); }
        $path = tempnam(sys_get_temp_dir(), 'office-test-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('hello.txt', 'not an Office document');
        $zip->close();
        try {
            $this->expectException(ValidationException::class);
            MediaFormats::inspect(new UploadedFile($path, 'fake.docx', null, UPLOAD_ERR_OK, true), true);
        } finally { @unlink($path); }
    }

    public function test_oversized_upload_returns_a_validation_error(): void
    {
        config(['dura-media.max_document_size_kb' => 1]);
        $this->expectException(ValidationException::class);
        MediaFormats::inspect(UploadedFile::fake()->createWithContent('large.txt', str_repeat('a', 2048)), true);
    }
}
