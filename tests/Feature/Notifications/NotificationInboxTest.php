<?php

declare(strict_types=1);

use App\Domain\Notifications\Models\Notification;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
    $this->withoutVite();
});

function makeNotificationFor(User $user, array $overrides = []): Notification
{
    return Notification::query()->create(array_merge([
        'type' => 'document.published',
        'recipient_user_id' => $user->id,
        'document_id' => null,
        'email_status' => 'sent',
        'metadata' => ['source' => 'test'],
        'created_at' => now(),
    ], $overrides));
}

it('redirects guests away from the inbox page', function (): void {
    $this->get(route('notifications.inbox'))->assertRedirect('/login');
});

it('only shows the authenticated user their own notifications on the inbox page', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    makeNotificationFor($userA, ['metadata' => ['marker' => 'visible-to-A']]);
    makeNotificationFor($userB, ['metadata' => ['marker' => 'belongs-to-B']]);

    actingAs($userA);

    $response = $this->get(route('notifications.inbox'));
    $response->assertOk();

    $items = $response->viewData('items');
    expect($items->total())->toBe(1);
    expect($items->first()->recipient_user_id)->toBe($userA->id);
});

it('returns the latest notifications JSON payload for the authenticated user only', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    makeNotificationFor($userB);
    $latest = makeNotificationFor($userA, ['created_at' => now()]);
    $older = makeNotificationFor($userA, ['created_at' => now()->subHour()]);

    actingAs($userA);

    $response = $this->getJson(route('notifications.latest'));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect($data[0]['id'])->toBe($latest->id);
    expect($data[1]['id'])->toBe($older->id);

    foreach ($data as $entry) {
        $stored = Notification::find($entry['id']);
        expect($stored->recipient_user_id)->toBe($userA->id);
    }
});

it('honors the limit query parameter on the latest endpoint', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 8) as $i) {
        makeNotificationFor($user, ['created_at' => now()->subMinutes($i)]);
    }

    actingAs($user);

    $response = $this->getJson(route('notifications.latest', ['limit' => 3]));
    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('blocks unauthenticated requests to the latest endpoint', function (): void {
    $this->getJson(route('notifications.latest'))->assertStatus(401);
});

it('marks a notification as read when the user opens it', function (): void {
    $user = User::factory()->create();
    $notification = makeNotificationFor($user);

    expect($notification->read_at)->toBeNull();

    actingAs($user);
    $response = $this->post(route('notifications.read', ['notification' => $notification->id]));

    $response->assertRedirect();
    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('forbids marking another user notification as read', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $notification = makeNotificationFor($userB);

    actingAs($userA);
    $response = $this->post(route('notifications.read', ['notification' => $notification->id]));
    $response->assertStatus(403);

    expect($notification->refresh()->read_at)->toBeNull();
});

it('marks all unread notifications as read for the current user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    makeNotificationFor($user);
    makeNotificationFor($user, ['read_at' => now()->subDay()]);
    makeNotificationFor($user);
    makeNotificationFor($other);

    actingAs($user);
    $response = $this->postJson(route('notifications.read-all'));
    $response->assertOk();

    $unread = Notification::query()
        ->where('recipient_user_id', $user->id)
        ->whereNull('read_at')
        ->count();

    expect($unread)->toBe(0);

    $otherUnread = Notification::query()
        ->where('recipient_user_id', $other->id)
        ->whereNull('read_at')
        ->count();

    expect($otherUnread)->toBe(1);
});

it('exposes unread_count in the latest payload', function (): void {
    $user = User::factory()->create();

    makeNotificationFor($user);
    makeNotificationFor($user, ['read_at' => now()]);
    makeNotificationFor($user);

    actingAs($user);

    $response = $this->getJson(route('notifications.latest'));
    $response->assertOk();
    expect($response->json('unread_count'))->toBe(2);
});
