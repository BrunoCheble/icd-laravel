<?php

namespace Tests\Feature\Members;

use App\Enums\MembershipStatus;
use App\Models\User;
use App\Modules\Members\Models\Member;
use App\Support\ImageResizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * A member's photo: a phone's large picture is accepted and made light (at most 1200 px).
 */
class MemberPhotoTest extends TestCase
{
    use RefreshDatabase;

    private ?string $saved = null;

    protected function tearDown(): void
    {
        if ($this->saved && is_file(public_path('images/' . $this->saved))) {
            unlink(public_path('images/' . $this->saved));
        }
        parent::tearDown();
    }

    public function test_a_large_photo_is_made_light(): void
    {
        $member = Member::create(['first_name' => 'ZZ Teste', 'last_name' => 'Foto', 'document_number' => '970000001',
            'gender' => 'M', 'marital_status' => 'S', 'membership_status' => MembershipStatus::ACTIVED]);
        $file = UploadedFile::fake()->image('foto.png', 3000, 2000)->size(5000);

        $this->actingAs(User::factory()->create())->patch(route('members.uploadPhoto', $member->id), ['photo' => $file])->assertRedirect();

        $this->saved = $member->fresh()->photo;
        $this->assertNotNull($this->saved);
        [$width, $height] = getimagesize(public_path('images/' . $this->saved));
        $this->assertSame([ImageResizer::MAX, 800], [$width, $height]);
        $this->assertMatchesRegularExpression('/\.(jpg|png)$/', $this->saved);
    }

    public function test_what_cannot_be_read_is_left_to_the_caller(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'zz');
        file_put_contents($file, 'not a picture');
        $this->assertNull(ImageResizer::resize($file, sys_get_temp_dir() . '/zz-out'));
        unlink($file);
    }
}
