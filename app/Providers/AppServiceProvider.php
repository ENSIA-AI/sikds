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

        // RAG queries hit the Groq LLM — strict per-user limit to prevent token drain.
        RateLimiter::for('rag', fn (Request $request) => Limit::perMinute(10)
            ->by((string) ($request->user()?->id ?: $request->ip())));

        // Watermarked downloads are CPU/disk-bound (qpdf + FPDI) but cheaper than LLM
        // calls, so they get a separate, more permissive limiter.
        RateLimiter::for('download', fn (Request $request) => Limit::perMinute(30)
            ->by((string) ($request->user()?->id ?: $request->ip())));

        // JSON documents API (session-authenticated, but still bounded so a
        // scripted client can't hammer list/detail endpoints).
        RateLimiter::for('api-documents', fn (Request $request) => Limit::perMinute(60)
            ->by((string) ($request->user()?->id ?: $request->ip())));

        // Uploads hash the file (SHA-256) and write to object storage — stricter
        // than the general documents API limiter.
        RateLimiter::for('api-documents-upload', fn (Request $request) => Limit::perMinute(10)
            ->by((string) ($request->user()?->id ?: $request->ip())));
    }
}
