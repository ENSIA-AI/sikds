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

    // Dot-notation path inside the SSO userinfo response that contains the user's role list.
    'roles_path' => env('SSO_ROLES_PATH', 'roles'),

    // Map SSO role code (uppercased) → system role name. Logins whose SSO profile
    // contains none of these role codes are rejected.
    //
    // The MESRS SSO (accounts.mesrs.dz) returns roles under
    // `individu.affectation[].role.libelle_long_fr` (see SSO_ROLES_PATH), and the
    // SIKDS application role is labelled "Secure Documentation Information and
    // Management System [manager|user]" rather than SKIDS_MANAGER/SKIDS_USER.
    // Keys are matched case-insensitively.
    'authorized_roles' => [
        'SKIDS_MANAGER' => 'Manager',
        'SKIDS_USER' => 'User',
        'Secure Documentation Information and Management System [manager]' => 'Manager',
        'Secure Documentation Information and Management System [user]' => 'User',
    ],
];
