<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Rag\Contracts\EmbeddingServiceInterface;
use App\Services\Rag\Contracts\RerankerServiceInterface;
use App\Services\Rag\Contracts\TokenEstimatorInterface;
use App\Services\Rag\GenericEmbeddingService;
use App\Services\Rag\GenericRerankerService;
use App\Services\Rag\TokenEstimator;
use Illuminate\Support\ServiceProvider;

class RagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TokenEstimatorInterface::class, TokenEstimator::class);
        $this->app->bind(EmbeddingServiceInterface::class, GenericEmbeddingService::class);
        $this->app->bind(RerankerServiceInterface::class, GenericRerankerService::class);
    }
}
