<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Documents\Services\Api\DocumentApiCommandService;
use App\Domain\Documents\Services\Api\DocumentApiQueryService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Documents\ListDocumentsRequest;
use App\Http\Requests\Api\Documents\StoreDocumentsRequest;
use App\Http\Requests\Api\Documents\UpdateDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class DocumentsApiController extends Controller
{
    public function __construct(
        private readonly DocumentApiQueryService $queryService,
        private readonly DocumentApiCommandService $commandService,
    ) {}

    public function index(ListDocumentsRequest $request): JsonResponse
    {
        return response()->json($this->queryService->index($request, $this->authUser()));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->queryService->show($id, $this->authUser()));
    }

    public function store(StoreDocumentsRequest $request): JsonResponse
    {
        return response()->json(
            $this->commandService->store($request, $this->authUser()),
            Response::HTTP_CREATED
        );
    }

    public function update(UpdateDocumentRequest $request, int $id): JsonResponse
    {
        $updated = $this->commandService->update($request, $id, $this->authUser());

        return response()->json([
            'message' => 'Document mis à jour.',
            'document' => $updated,
        ]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $document = $this->commandService->publish($request, $id, $this->authUser());

        return response()->json([
            'message' => 'Document publié.',
            'document' => $document,
        ]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $document = $this->commandService->archive($request, $id, $this->authUser());

        return response()->json([
            'message' => 'Document archivé.',
            'document' => $document,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->commandService->softDelete($request, $id, $this->authUser());

        return response()->json(['message' => 'Document supprimé (soft-delete).']);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $document = $this->commandService->restore($request, $id, $this->authUser());

        return response()->json([
            'message' => 'Document restauré.',
            'document' => $document,
        ]);
    }

    public function versions(int $id): JsonResponse
    {
        return response()->json($this->queryService->versions($id, $this->authUser()));
    }

    private function authUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
