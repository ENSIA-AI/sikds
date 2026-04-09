<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rows = [
            ['code' => 'document.create', 'category' => 'documents', 'description' => 'Créer de nouveaux documents.'],
            ['code' => 'document.view.all', 'category' => 'documents', 'description' => 'Consulter tous les documents du système.'],
            ['code' => 'document.view.own_institution', 'category' => 'documents', 'description' => 'Consulter les documents de son institution.'],
            ['code' => 'document.view.assigned', 'category' => 'documents', 'description' => 'Consulter les documents assignés.'],
            ['code' => 'document.edit', 'category' => 'documents', 'description' => 'Modifier les documents.'],
            ['code' => 'document.delete', 'category' => 'documents', 'description' => 'Supprimer des documents.'],
            ['code' => 'document.restore', 'category' => 'documents', 'description' => 'Restaurer un document supprimé.'],
            ['code' => 'document.publish', 'category' => 'documents', 'description' => 'Publier un document.'],
            ['code' => 'distribution.manage', 'category' => 'documents', 'description' => 'Gérer la distribution des documents.'],
            ['code' => 'tag.assign', 'category' => 'documents', 'description' => 'Assigner des étiquettes aux documents.'],
            ['code' => 'tag.manage', 'category' => 'documents', 'description' => 'Gérer les étiquettes.'],
            ['code' => 'user.manage', 'category' => 'users', 'description' => 'Gérer les utilisateurs.'],
            ['code' => 'user.deactivate', 'category' => 'users', 'description' => 'Désactiver des utilisateurs.'],
            ['code' => 'user.view.all', 'category' => 'users', 'description' => 'Voir tous les utilisateurs.'],
            ['code' => 'user.assign.permissions', 'category' => 'users', 'description' => 'Assigner des permissions.'],
            ['code' => 'role.create', 'category' => 'users', 'description' => 'Créer des rôles.'],
            ['code' => 'role.edit', 'category' => 'users', 'description' => 'Modifier des rôles.'],
            ['code' => 'role.delete', 'category' => 'users', 'description' => 'Supprimer des rôles.'],
            ['code' => 'role.view', 'category' => 'users', 'description' => 'Voir les rôles.'],
            ['code' => 'institution.create', 'category' => 'users', 'description' => 'Créer des institutions.'],
            ['code' => 'institution.edit', 'category' => 'users', 'description' => 'Modifier des institutions.'],
            ['code' => 'institution.delete', 'category' => 'users', 'description' => 'Supprimer des institutions.'],
            ['code' => 'institution.view', 'category' => 'users', 'description' => 'Voir les institutions.'],
            ['code' => 'rag.query', 'category' => 'rag', 'description' => 'Requêtes RAG.'],
            ['code' => 'search.basic', 'category' => 'rag', 'description' => 'Recherche simple.'],
            ['code' => 'audit.view', 'category' => 'audit', 'description' => 'Consulter les journaux d’audit.'],
        ];

        foreach ($rows as $row) {
            Permission::query()->updateOrCreate(
                ['name' => $row['code'], 'guard_name' => 'web'],
                [
                    'code' => $row['code'],
                    'description' => $row['description'],
                    'category' => $row['category'],
                ]
            );
        }
    }
}
