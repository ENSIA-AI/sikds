<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Horizon\Events\LongWaitDetected;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();

        Queue::failing(function (JobFailed $event): void {
            $payload = $event->job->payload();

            AuditLog::query()->create([
                'event_type' => 'QUEUE_JOB_FAILED',
                'user_id' => null,
                'user_email' => null,
                'resource_type' => 'queue',
                'resource_id' => 0,
                'metadata' => [
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'job_name' => (string) ($payload['displayName'] ?? $event->job->resolveName()),
                    'job_id' => $event->job->getJobId(),
                    'exception' => $event->exception->getMessage(),
                ],
                'result' => 'failed',
                'ip_address' => null,
                'user_agent' => null,
                'created_at' => now(),
            ]);
        });

        Event::listen(function (LongWaitDetected $event): void {
            foreach ($event->queues as $queue) {
                AuditLog::query()->create([
                    'event_type' => 'QUEUE_LONG_WAIT_DETECTED',
                    'user_id' => null,
                    'user_email' => null,
                    'resource_type' => 'queue',
                    'resource_id' => 0,
                    'metadata' => [
                        'connection' => $event->connectionName,
                        'queue' => $queue,
                        'wait_seconds' => $event->waitTime,
                    ],
                    'result' => 'warning',
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => now(),
                ]);
            }
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $email = Str::lower((string) $request->input('email'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(5)->by($email.'|'.$ip),
                Limit::perMinute(20)->by($ip),
            ];
        });

        RateLimiter::for('sso', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));
    }
}
