<?php

return [
    'client_id' => env('CLIENT_ID'),
    'client_secret' => env('CLIENT_SECRET'),
    'redirect_uri' => env('REDIRECT_URI'),
    'server' => env('SSO_SERVER'),
    'authorize_path' => env('SSO_AUTHORIZE_PATH', '/oauth/authorize'),
    'token_path' => env('SSO_TOKEN_PATH', '/oauth/token'),
    'userinfo_path' => env('SSO_USERINFO_PATH', '/api/user'),
    'scope' => env('SSO_SCOPE', ''),
    'allowed_domains' => array_values(array_filter(array_map(
        static fn(string $domain): string => strtolower(trim($domain)),
        explode(',', (string) env('SSO_ALLOWED_DOMAINS', ''))
    ))),

    // Dot-notation path (wildcards allowed) into the SSO userinfo response that
    // yields this user's role labels. The ministry SSO exposes grants under each
    // `affectation`, e.g. individu.affectation[].role.libelle_long_fr =
    // "Secure Documentation Information and Management System [manager]".
    'roles_path' => env('SSO_ROLES_PATH', 'individu.affectation.*.role.libelle_long_fr'),

    // A login is authorized if any of its SSO role labels contains this marker
    // (case-insensitive). This is the application's name as registered in the
    // ministry SSO, NOT the local "SIKDS" spelling.
    'app_role_marker' => env('SSO_APP_ROLE_MARKER', 'secure documentation information and management system'),

    // Within an authorized label, bracketed qualifiers select the system role,
    // evaluated in priority order: admin first, then manager, otherwise user.
    // A label matching the marker with no admin/manager qualifier maps to User.
    'admin_role_qualifiers' => array_values(array_filter(array_map(
        static fn (string $q): string => strtolower(trim($q)),
        explode(',', (string) env('SSO_ADMIN_ROLE_QUALIFIERS', '[admin],[super-admin],[superadmin],[administrateur]'))
    ))),
    'manager_role_qualifiers' => array_values(array_filter(array_map(
        static fn (string $q): string => strtolower(trim($q)),
        explode(',', (string) env('SSO_MANAGER_ROLE_QUALIFIERS', '[manager],[gestionnaire]'))
    ))),

    // System role names (must match seeded roles) that the qualifiers resolve to.
    'admin_system_role' => env('SSO_ADMIN_SYSTEM_ROLE', 'Super Administrateur'),
    'manager_system_role' => env('SSO_MANAGER_SYSTEM_ROLE', 'Manager'),
    'user_system_role' => env('SSO_USER_SYSTEM_ROLE', 'User'),
];
