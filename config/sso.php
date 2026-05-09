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
    'authorized_roles' => [
        'SKIDS_USER' => 'User',
        'SKIDS_MANAGER' => 'Manager',
    ],
];
