<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_open_and_mark_notifications_as_read(): void
    {
        $this->seed();
        $user = $this->user('NOTIFY-1');
        $user->notify(new WorkflowNotification('Transfer approved', 'Proceed with physical handover.', route('movements.index')));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->get(route('notifications.index', ['status' => 'unread']))->assertOk()->assertSee('Transfer approved');
        $this->actingAs($user)->get(route('notifications.open', $notification->id))->assertRedirect(route('movements.index'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $this->seed();
        $owner = $this->user('NOTIFY-2');
        $other = $this->user('NOTIFY-3');
        $owner->notify(new WorkflowNotification('Private update', 'Only the intended user may open this.', route('dashboard')));

        $this->actingAs($other)->post(route('notifications.read', $owner->notifications()->first()->id))->assertNotFound();
    }

    public function test_mark_all_read_only_affects_authenticated_user(): void
    {
        $this->seed();
        $first = $this->user('NOTIFY-4');
        $second = $this->user('NOTIFY-5');
        $first->notify(new WorkflowNotification('First', 'First message', route('dashboard')));
        $second->notify(new WorkflowNotification('Second', 'Second message', route('dashboard')));
        $this->actingAs($first)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $first->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $second->fresh()->unreadNotifications()->count());
    }

    private function user(string $staff): User
    {
        $admin = User::where('email', 'admin@eims.local')->firstOrFail();
        $user = User::create(['public_id' => (string) Str::ulid(), 'staff_number' => $staff, 'name' => $staff, 'email' => strtolower($staff).'@eims.local', 'status' => 'active', 'organizational_unit_id' => $admin->organizational_unit_id, 'department_id' => $admin->department_id, 'primary_location_id' => $admin->primary_location_id, 'password' => 'Password123']);
        $user->roles()->attach(Role::where('slug', 'staff-member')->value('id'), ['assigned_by' => $admin->id, 'assigned_at' => now()]);

        return $user;
    }
}
