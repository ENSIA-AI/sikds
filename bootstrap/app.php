<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SecurityHeaders;
use App\Domain\Users\Exceptions\SsoAuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // All routes are session-authenticated and served through the `web`
        // middleware group (there is no stateless `api:` route file / token guard).
        // CSRF protection therefore stays on for every route; the frontend sends
        // the token via the `<meta name="csrf-token">` → `X-CSRF-TOKEN` header.
        $middleware->alias([
            // Spatie middleware (see routes/functionalities.php docblock for `can:` vs `permission:`).
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->appendToGroup('web', [
            SetLocale::class,
            EnsureUserIsActive::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApiRequest = static fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, \Throwable $e): bool => $isApiRequest($request)
        );

        $exceptions->dontReportWhen(
            static fn (\Throwable $e): bool => $e instanceof SsoAuthenticationException && ! $e->shouldReport()
        );

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => __('Les données fournies sont invalides.'),
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            // 419 is Laravel's CSRF status (no Symfony Response constant exists for it).
            return response()->json([
                'message' => __('Votre session a expiré. Veuillez actualiser la page et réessayer.'),
            ], 419);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => __('Authentification requise.'),
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => __('Vous n’avez pas l’autorisation d’effectuer cette action.'),
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => __('Ressource introuvable.'),
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = match (true) {
                $status >= Response::HTTP_INTERNAL_SERVER_ERROR => __('Une erreur serveur est survenue. Veuillez réessayer.'),
                $status === Response::HTTP_FORBIDDEN && $e->getMessage() === 'This action is unauthorized.' => __('Vous n’avez pas l’autorisation d’effectuer cette action.'),
                default => __(($e->getMessage() ?: Response::$statusTexts[$status] ?? 'Erreur.')),
            };

            return response()->json(['message' => $message], $status);
        });

        $exceptions->render(function (\Throwable $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json([
                'message' => __('Une erreur serveur est survenue. Veuillez réessayer.'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
