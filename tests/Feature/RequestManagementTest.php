<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Request;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(): User
    {
        return User::factory()->create(['type' => 'buyer']);
    }

    public function test_buyer_can_view_the_create_page(): void
    {
        $this->actingAs($this->buyer())
            ->get(route('requests.create'))
            ->assertOk();
    }

    public function test_merchant_cannot_access_request_routes(): void
    {
        $merchant = User::factory()->merchant()->create();

        $this->actingAs($merchant)
            ->get(route('requests.index'))
            ->assertForbidden();
    }

    public function test_buyer_can_save_a_draft_request(): void
    {
        $buyer = $this->buyer();
        $category = Category::factory()->create();

        Volt::actingAs($buyer)->test('requests.create')
            ->set('form.title', 'محتاج لابتوب للألعاب')
            ->set('form.category_id', $category->id)
            ->set('form.budget_min', 20000)
            ->set('form.budget_max', 35000)
            ->call('save', false)
            ->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'buyer_id' => $buyer->id,
            'title' => 'محتاج لابتوب للألعاب',
            'status' => 'draft',
        ]);
    }

    public function test_buyer_can_publish_on_creation_with_images(): void
    {
        Storage::fake('public');
        $buyer = $this->buyer();
        $category = Category::factory()->create();

        Volt::actingAs($buyer)->test('requests.create')
            ->set('form.title', 'مطلوب موبايل')
            ->set('form.category_id', $category->id)
            ->set('images', [UploadedFile::fake()->image('phone.jpg')])
            ->call('save', true)
            ->assertRedirect();

        $request = Request::firstWhere('title', 'مطلوب موبايل');
        $this->assertSame('open', $request->status);
        $this->assertNotNull($request->published_at);
        $this->assertCount(1, $request->attachments);
        Storage::disk('public')->assertExists($request->attachments->first()->path);
    }

    public function test_create_requires_a_title_and_category(): void
    {
        Volt::actingAs($this->buyer())->test('requests.create')
            ->set('form.title', '')
            ->call('save', false)
            ->assertHasErrors(['form.title', 'form.category_id']);
    }

    public function test_budget_max_must_be_at_least_budget_min(): void
    {
        $category = Category::factory()->create();

        Volt::actingAs($this->buyer())->test('requests.create')
            ->set('form.title', 'تجربة')
            ->set('form.category_id', $category->id)
            ->set('form.budget_min', 5000)
            ->set('form.budget_max', 1000)
            ->call('save', false)
            ->assertHasErrors(['form.budget_max']);
    }

    public function test_wizard_steps_require_category_then_title(): void
    {
        $category = Category::factory()->create();

        $c = Volt::actingAs($this->buyer())->test('requests.create');

        // Step 1 → can't advance without a category.
        $c->call('next')->assertHasErrors('form.category_id');
        $this->assertSame(1, $c->get('step'));

        // Pick a category → advance to step 2.
        $c->call('selectCategory', $category->id)->call('next');
        $this->assertSame(2, $c->get('step'));

        // Step 2 → can't advance without a title.
        $c->set('form.title', '')->call('next')->assertHasErrors('form.title');
    }

    public function test_category_specifications_are_saved(): void
    {
        $buyer = $this->buyer();
        $category = Category::factory()->create();

        Volt::actingAs($buyer)->test('requests.create')
            ->set('form.category_id', $category->id)
            ->set('form.title', 'آيفون مستعمل')
            ->set('form.specifications.brand', 'Apple')
            ->set('form.specifications.storage', '256GB')
            ->call('save', false)
            ->assertRedirect();

        $request = Request::firstWhere('title', 'آيفون مستعمل');
        $this->assertSame(['brand' => 'Apple', 'storage' => '256GB'], $request->specifications);
    }

    public function test_buyer_cannot_view_another_buyers_request(): void
    {
        $owner = $this->buyer();
        $other = $this->buyer();
        $request = Request::factory()->for($owner, 'buyer')->create();

        $this->actingAs($other)
            ->get(route('requests.show', $request))
            ->assertForbidden();
    }

    public function test_buyer_can_publish_a_draft_from_show_page(): void
    {
        $buyer = $this->buyer();
        $request = Request::factory()->for($buyer, 'buyer')->create(['status' => 'draft']);

        Volt::actingAs($buyer)->test('requests.show', ['request' => $request])
            ->call('publish');

        $this->assertSame('open', $request->fresh()->status);
    }

    public function test_buyer_can_delete_their_request(): void
    {
        $buyer = $this->buyer();
        $request = Request::factory()->for($buyer, 'buyer')->create();

        Volt::actingAs($buyer)->test('requests.show', ['request' => $request])
            ->call('delete')
            ->assertRedirect(route('requests.index'));

        $this->assertModelMissing($request);
    }
}
