<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Users\Models\Permission;
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
            ['code' => 'document.forward', 'category' => 'documents', 'description' => 'Transférer un document à un autre utilisateur.'],
            ['code' => 'distribution.manage', 'category' => 'distribution', 'description' => 'Gérer la distribution des documents.'],
            ['code' => 'tag.assign', 'category' => 'tags', 'description' => 'Assigner des étiquettes aux documents.'],
            ['code' => 'tag.manage', 'category' => 'tags', 'description' => 'Gérer les étiquettes.'],
            ['code' => 'user.manage', 'category' => 'users', 'description' => 'Gérer les utilisateurs.'],
            ['code' => 'user.deactivate', 'category' => 'users', 'description' => 'Désactiver des utilisateurs.'],
            ['code' => 'user.view.all', 'category' => 'users', 'description' => 'Voir tous les utilisateurs.'],
            ['code' => 'user.create', 'category' => 'users', 'description' => 'Créer des utilisateurs.'],
            ['code' => 'user.assign.permissions', 'category' => 'users', 'description' => 'Assigner des permissions.'],
            ['code' => 'role.create', 'category' => 'roles', 'description' => 'Créer des rôles.'],
            ['code' => 'role.edit', 'category' => 'roles', 'description' => 'Modifier des rôles.'],
            ['code' => 'role.delete', 'category' => 'roles', 'description' => 'Supprimer des rôles.'],
            ['code' => 'role.view', 'category' => 'roles', 'description' => 'Voir les rôles.'],
            ['code' => 'institution.create', 'category' => 'institutions', 'description' => 'Créer des institutions.'],
            ['code' => 'institution.edit', 'category' => 'institutions', 'description' => 'Modifier des institutions.'],
            ['code' => 'institution.delete', 'category' => 'institutions', 'description' => 'Supprimer des institutions.'],
            ['code' => 'institution.view', 'category' => 'institutions', 'description' => 'Voir les institutions.'],
            ['code' => 'rag.query', 'category' => 'rag', 'description' => 'Requêtes RAG.'],
            ['code' => 'search.basic', 'category' => 'rag', 'description' => 'Recherche simple.'],
            ['code' => 'indexing.manage', 'category' => 'indexing', 'description' => 'Accéder au moniteur d’indexation et relancer les indexations en échec.'],
            ['code' => 'audit.view', 'category' => 'audit', 'description' => 'Consulter les journaux d’audit.'],
            ['code' => 'settings.view', 'category' => 'settings', 'description' => 'Consulter les paramètres système.'],
            ['code' => 'settings.manage', 'category' => 'settings', 'description' => 'Modifier les paramètres système.'],
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
