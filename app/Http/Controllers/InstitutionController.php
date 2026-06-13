<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\Models\Institution;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('institution.view');

        $stats = [
            'total_institutions' => Institution::query()->count(),
            'active_institutions' => Institution::query()->where('is_active', true)->count(),
            'total_users' => (int) DB::table('users')->count(),
            'total_documents' => (int) DB::table('documents')->whereNull('deleted_at')->count(),
        ];

        $institutions = Institution::query()
            ->withCount(['users', 'documentInstitutionTargets as documents_count'])
            ->orderBy('name')
            ->get();

        return view('institutions', [
            'institutions' => $institutions,
            'stats' => $stats,
        ]);
    }

    public function store(StoreInstitutionRequest $request): JsonResponse
    {
        $this->authorize('institution.create');

        $validated = $request->validated();
        unset($validated['logo']);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('institution-logos', 'public');
        }

        $validated['type'] = $validated['type'] ?? 'university';
        $validated['is_active'] = true;

        $institution = Institution::query()->create($validated);
        $institution->loadCount(['users', 'documentInstitutionTargets as documents_count']);

        if (! $request->wantsJson()) {
            return response()->json(['message' => __('Accept JSON requis.')], 406);
        }

        $cardHtml = view('components.institution-card', [
            'institution' => $institution,
        ])->render();

        return response()->json([
            'message' => __('L\'institution « :name » a été créée.', ['name' => $institution->name]),
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'code' => $institution->code,
            ],
            'html' => $cardHtml,
        ]);
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): JsonResponse
    {
        $this->authorize('institution.edit');

        $validated = $request->validated();
        unset($validated['logo']);

        if ($request->hasFile('logo')) {
            if ($institution->logo_path) {
                Storage::disk('public')->delete($institution->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('institution-logos', 'public');
        }

        $institution->update($validated);
        $institution->loadCount(['users', 'documentInstitutionTargets as documents_count']);

        if (! $request->wantsJson()) {
            return response()->json(['message' => __('Accept JSON requis.')], 406);
        }

        $cardHtml = view('components.institution-card', [
            'institution' => $institution,
        ])->render();

        return response()->json([
            'message' => __('L\'institution « :name » a été mise à jour.', ['name' => $institution->name]),
            'html' => $cardHtml,
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'code' => $institution->code,
                'contact_email' => $institution->contact_email,
                'contact_phone' => $institution->contact_phone,
                'address' => $institution->address,
                'logo_public_url' => $institution->logo_public_url,
            ],
        ]);
    }

    public function destroy(Request $request, Institution $institution): JsonResponse
    {
        $this->authorize('institution.delete');

        $deletedId = $institution->id;

        try {
            if ($institution->logo_path) {
                Storage::disk('public')->delete($institution->logo_path);
            }
            $institution->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => __('Impossible de supprimer cette institution : des utilisateurs y sont encore rattachés.'),
            ], 422);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __('L\'institution a été supprimée.'),
                'id' => $deletedId,
            ]);
        }

        return response()->json(['message' => __('Accept JSON requis.')], 406);
    }
}
