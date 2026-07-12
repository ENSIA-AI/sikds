<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use App\Domain\Institutions\Models\Institution;
use App\Domain\Tags\Models\Tag;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'reference_number',
        'title',
        'description',
        'file_path',
        'file_hash',
        'file_size',
        'issue_date',
        'effective_date',
        'expiration_date',
        'status',          // draft, active, archived, soft_deleted
        'indexing_status', // pending, processing, indexed, failed
        'target_audience', // all, specific_institutions, specific_roles, specific_users
        'version_number',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'file_size' => 'integer',
        'version_number' => 'integer',
    ];

    /**
     * The user who uploaded this document version.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Institutions this document is targeted to.
     */
    public function targetInstitutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'document_institution_targets', 'document_id', 'institution_id');
    }

    /**
     * Roles this document is targeted to.
     */
    public function targetRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'document_role_targets', 'document_id', 'role_id');
    }

    /**
     * Tags assigned to this document.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tags', 'document_id', 'tag_id');
    }

    /**
     * Users this document is directly targeted to.
     */
    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_user_targets', 'document_id', 'user_id');
    }

    /**
     * Determines whether the given user is authorised to access this document.
     * Priority: document.view.all > audience=all > specific_institutions > specific_roles > direct user target.
     */
    public function isAccessibleBy(User $user): bool
    {
        return self::query()
            ->whereKey($this->id)
            ->visibleTo($user)
            ->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user, bool $includeUploader = true): Builder
    {
        if ($user->can('document.view.all')) {
            return $query;
        }

        $institutionId = $user->institution_id;
        // Use the (lazily loaded, then cached) `roles` relation instead of a fresh
        // query so repeated visibleTo()/isAccessibleBy() calls within one request
        // resolve role ids once instead of re-querying per call.
        $roleIds = $user->roles->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return $query->where(function (Builder $sub) use ($user, $includeUploader, $institutionId, $roleIds): void {
            if ($includeUploader) {
                $sub->where('uploaded_by', $user->id)
                    ->orWhere(function (Builder $activeScope) use ($user, $institutionId, $roleIds): void {
                        $this->applyActiveAudienceScope($activeScope, $user, $institutionId, $roleIds);
                    });

                return;
            }

            $this->applyActiveAudienceScope($sub, $user, $institutionId, $roleIds);
        });
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    private function applyActiveAudienceScope(Builder $query, User $user, ?int $institutionId, array $roleIds): void
    {
        $query->where('status', 'active')
            ->where(function (Builder $outer) use ($user, $institutionId, $roleIds): void {
                $outer->where('target_audience', 'all')
                    ->orWhere(function (Builder $s) use ($institutionId): void {
                        if ($institutionId === null) {
                            $s->whereRaw('1 = 0');

                            return;
                        }

                        $s->where('target_audience', 'specific_institutions')
                            ->whereExists(function ($sub) use ($institutionId): void {
                                $sub->selectRaw('1')
                                    ->from('document_institution_targets')
                                    ->whereColumn('document_institution_targets.document_id', 'documents.id')
                                    ->where('document_institution_targets.institution_id', $institutionId);
                            });
                    })
                    ->orWhere(function (Builder $s) use ($roleIds): void {
                        if ($roleIds === []) {
                            $s->whereRaw('1 = 0');

                            return;
                        }

                        $s->where('target_audience', 'specific_roles')
                            ->whereExists(function ($sub) use ($roleIds): void {
                                $sub->selectRaw('1')
                                    ->from('document_role_targets')
                                    ->whereColumn('document_role_targets.document_id', 'documents.id')
                                    ->whereIn('document_role_targets.role_id', $roleIds);
                            });
                    })
                    ->orWhereExists(function ($sub) use ($user): void {
                        $sub->selectRaw('1')
                            ->from('document_user_targets')
                            ->whereColumn('document_user_targets.document_id', 'documents.id')
                            ->where('document_user_targets.user_id', $user->id)
                            // A direct user target only grants access when it is an
                            // intentional grant: either the document's audience is
                            // explicitly `specific_users`, or the row was created by the
                            // forward feature (which sets `assigned_by`). This prevents a
                            // stray `target_user_ids` row from over-matching a document
                            // whose audience is `all`/`specific_institutions`/`specific_roles`.
                            ->where(function ($audienceGate): void {
                                $audienceGate->where('documents.target_audience', 'specific_users')
                                    ->orWhereNotNull('document_user_targets.assigned_by');
                            });
                    });
            });
    }

    /**
     * Scope to quickly get only active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Escape `%` and `_` in user input destined for a LIKE clause so it
     * matches literally.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
