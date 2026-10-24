<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\DocumentType;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->family = Family::factory()->create();
        $this->application = Application::factory()->submitted()->for($this->family)->create();
        $this->actingAs($this->family, 'family');
    }

    private function upload(UploadedFile $file, string $type = 'birth_certificate'): TestResponse
    {
        return $this->from(route('family.applications.show', $this->application))
            ->post(route('family.documents.store', $this->application), ['type' => $type, 'file' => $file]);
    }

    public function test_pdf_and_photos_are_accepted_and_stored_privately(): void
    {
        $this->upload(UploadedFile::fake()->create('birth.pdf', 500, 'application/pdf'))->assertSessionHasNoErrors();
        $this->upload(UploadedFile::fake()->image('card.jpg', 1200, 900), 'immunisation')->assertSessionHasNoErrors();
        $this->upload(UploadedFile::fake()->image('reports.png'), 'school_reports')->assertSessionHasNoErrors();

        $this->assertSame(3, $this->application->documents()->count());
        $doc = $this->application->documents()->firstWhere('type', 'birth_certificate');
        Storage::disk('local')->assertExists($doc->path);
        $this->assertStringStartsWith('admissions/'.$this->application->id.'/', $doc->path);
        $this->assertStringNotContainsString('birth.pdf', $doc->path, 'stored under a random name');
        $this->assertSame('pending', $doc->status);
        $this->assertFileDoesNotExist(public_path($doc->path));
    }

    public function test_dangerous_or_wrong_file_types_are_rejected(): void
    {
        $this->upload(UploadedFile::fake()->create('shell.php', 5, 'application/x-php'))->assertSessionHasErrors('file');
        $this->upload(UploadedFile::fake()->create('virus.exe', 5, 'application/x-msdownload'))->assertSessionHasErrors('file');
        $this->upload(UploadedFile::fake()->create('notes.docx', 5, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))->assertSessionHasErrors('file');
        // A PHP script renamed to .pdf is caught by content sniffing.
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, '<?php echo "pwned"; ?>');
        $this->upload(new UploadedFile($tmp, 'report.pdf', null, null, true))->assertSessionHasErrors('file');
        $this->assertSame(0, ApplicationDocument::count());
    }

    public function test_files_over_8mb_and_unknown_document_types_are_rejected(): void
    {
        $this->upload(UploadedFile::fake()->create('big.pdf', ApplicationDocument::MAX_KB + 1, 'application/pdf'))
            ->assertSessionHasErrors(['file' => 'Each file must be 8 MB or smaller.']);
        $this->upload(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'bank_statement')->assertSessionHasErrors('type');
        $this->post(route('family.documents.store', $this->application), ['type' => 'passport'])->assertSessionHasErrors('file');
        $this->assertSame(0, ApplicationDocument::count());
    }

    public function test_family_can_remove_a_pending_file_but_not_a_reviewed_one(): void
    {
        $this->upload(UploadedFile::fake()->create('wrong.pdf', 10, 'application/pdf'));
        $doc = $this->application->documents()->first();
        $this->delete(route('family.documents.destroy', [$this->application, $doc]))->assertRedirect();
        $this->assertModelMissing($doc);
        Storage::disk('local')->assertMissing($doc->path);

        $this->upload(UploadedFile::fake()->create('right.pdf', 10, 'application/pdf'));
        $kept = $this->application->documents()->first();
        $kept->update(['status' => 'accepted']);
        $this->delete(route('family.documents.destroy', [$this->application, $kept]))->assertForbidden();
    }

    public function test_family_can_download_its_own_document(): void
    {
        $this->upload(UploadedFile::fake()->create('birth.pdf', 10, 'application/pdf'));
        $doc = $this->application->documents()->first();
        $this->get(route('family.documents.show', [$this->application, $doc]))->assertOk()->assertDownload('birth.pdf');
    }

    public function test_staff_open_a_document_inline_with_an_encoded_file_name(): void
    {
        $this->upload(UploadedFile::fake()->create('Acta de nacimiento – José.pdf', 10, 'application/pdf'));
        $doc = $this->application->documents()->first();

        $disposition = (string) $this->actingAs(User::factory()->create(['role' => 'admissions']), 'web')
            ->get(route('admin.documents.show', $doc))->assertOk()->headers->get('Content-Disposition');

        $this->assertStringStartsWith('inline;', $disposition);
        $this->assertStringContainsString("filename*=utf-8''Acta%20de%20nacimiento%20%E2%80%93%20Jos%C3%A9.pdf", $disposition);
        $this->assertTrue(mb_check_encoding($disposition, 'ASCII'), 'the header itself stays plain ASCII');
    }

    public function test_uploads_close_once_an_application_is_finished(): void
    {
        $this->application->update(['status' => AdmissionStatus::Enrolled]);
        $this->upload(UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'))->assertForbidden();
    }

    public function test_missing_documents_depend_on_grade(): void
    {
        $k1 = Application::factory()->for($this->family)->create(['grade' => 'k1']);
        $this->assertNotContains(DocumentType::SchoolReports, $k1->missingDocuments());
        $this->assertContains(DocumentType::SchoolReports, $this->application->missingDocuments());
    }
}
