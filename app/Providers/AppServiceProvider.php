<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Audit\Services\AuditService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Horizon\Events\LongWaitDetected;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
      if (app()->environment('production')) {
            URL::forceScheme('https');
        }

      $this->configureRateLimiters();
        Queue::failing(function (JobFailed $event): void {
            $payload = $event->job->payload();

            app(AuditService::class)->record(
                eventType: 'queue.job.failed',
                result: 'failed',
                resourceType: 'queue',
                resourceId: 0,
                metadata: [
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'job_name' => (string) ($payload['displayName'] ?? $event->job->resolveName()),
                    'job_id' => $event->job->getJobId(),
                    'reason' => 'job_failed',
                    'message' => $event->exception->getMessage(),
                ],
            );
        });

        Event::listen(function (LongWaitDetected $event): void {
            foreach ($event->queues as $queue) {
                app(AuditService::class)->record(
                    eventType: 'queue.long_wait.detected',
                    result: 'warning',
                    resourceType: 'queue',
                    resourceId: 0,
                    metadata: [
                        'connection' => $event->connectionName,
                        'queue' => $queue,
                        'wait_seconds' => $event->waitTime,
                    ],
                );
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
