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
    // yields this user's role IDs. The ministry SSO exposes grants under each
    // `affectation`, e.g. individu.affectation[].role.id (1623, 1624, ...).
    'roles_path' => env('SSO_ROLES_PATH', 'individu.affectation.*.role.id'),

    // Ministry SSO role IDs → application system role name (must match a seeded
    // role). A login is authorized only if its profile carries at least one of
    // these IDs. When several are present, the highest-privilege role wins — see
    // `role_priority`. Role IDs are global and stable across the MESRS SSO.
    'role_id_map' => [
        1623 => 'User',
        1624 => 'Manager',
        // <id> => 'Super Administrateur',
    ],

    // System role names ordered from highest privilege to lowest. Used to pick a
    // single role when an account is granted more than one mapped SSO role.
    'role_priority' => ['Super Administrateur', 'Manager', 'User'],
];
