<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function co_user(string $role): User
{
    return User::firstOrCreate(['email' => "{$role}@test.local"], ['name' => "Test {$role}", 'password' => bcrypt('x'), 'role' => $role, 'status' => 'active']);
}

test('admin sees both companies and can edit their details and logo', function () {
    Storage::fake('public');
    $admin = co_user('admin');
    $xceed = Company::where('code', 'colossal-xceed')->first();

    $this->actingAs($admin)->get('/admin/companies')->assertOk()
        ->assertSeeInOrder(['COLOSSAL MEDIA SDN BHD', 'Default', 'COLOSSAL XCEED SDN BHD']);
    $this->actingAs($admin)->get("/admin/companies/{$xceed->id}/edit")->assertOk()->assertSee('200801020648');

    $this->actingAs($admin)->put("/admin/companies/{$xceed->id}", [
        'name' => 'COLOSSAL XCEED SDN BHD', 'reg_no' => '200801020648', 'address' => "Lot 1\nShah Alam",
        'phone' => '03-5103 6050', 'fax' => '', 'email' => 'sales@xceed.test', 'logo' => UploadedFile::fake()->image('logo.png', 600, 120),
    ])->assertRedirect('/admin/companies')->assertSessionHas('success');

    $xceed->refresh();
    expect($xceed->address)->toBe("Lot 1\nShah Alam")
        ->and($xceed->email)->toBe('sales@xceed.test')
        ->and($xceed->fax)->toBeNull()
        ->and($xceed->logo)->toStartWith('storage/companies/colossal-xceed-');
    Storage::disk('public')->assertExists(substr($xceed->logo, strlen('storage/')));

    // Saving without a new logo keeps the current one.
    $logo = $xceed->logo;
    $this->actingAs($admin)->put("/admin/companies/{$xceed->id}", ['name' => 'COLOSSAL XCEED SDN BHD'])->assertSessionHas('success');
    expect($xceed->refresh()->logo)->toBe($logo);
});

test('company details are validated and admin only', function () {
    $media = Company::default();

    $this->actingAs(co_user('admin'))->from("/admin/companies/{$media->id}/edit")
        ->put("/admin/companies/{$media->id}", ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    foreach (['boss', 'salesperson', 'operations-dispatch-control'] as $role) {
        $this->actingAs(co_user($role))->get('/admin/companies')->assertForbidden();
        $this->actingAs(co_user($role))->put("/admin/companies/{$media->id}", ['name' => 'X'])->assertForbidden();
    }
    expect($media->refresh()->name)->toBe('COLOSSAL MEDIA SDN BHD');
});
