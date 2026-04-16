<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
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
        'target_audience', // all, specific_institutions, specific_roles
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
     * Users this document is directly targeted to.
     */
    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_user_targets', 'document_id', 'user_id');
    }

    /**
     * Determines whether the given user is authorised to access this document.
     * Priority: document.view.all > audience=all > specific_institutions > specific_roles.
     */
    public function isAccessibleBy(User $user): bool
    {
        // Super-permission bypasses all audience rules only for Super-Admin.
        if ($user->can('document.view.all') && $user->hasRole('Super Administrateur')) {
            return true;
        }

        if ((int) $this->uploaded_by === (int) $user->id) {
            return true;
        }

        // Active status required for regular users
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->targetUsers()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return match ($this->target_audience) {
            'all' => true,
            'specific_institutions' => $user->institution_id !== null
                && $this->targetInstitutions()->where('institutions.id', $user->institution_id)->exists(),
            'specific_roles' => false,
            default => false,
        };
    }

    /**
     * Scope to quickly get only active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
