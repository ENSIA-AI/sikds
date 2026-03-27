<?php

return [
    'client_id' => env('CLIENT_ID'),
    'client_secret' => env('CLIENT_SECRET'),
    'redirect_uri' => env('REDIRECT_URI'),
    'server' => env('SSO_SERVER'),
    'authorize_path' => env('SSO_AUTHORIZE_PATH', '/oauth/authorize'),
    'token_path' => env('SSO_TOKEN_PATH', '/oauth/token'),
    'userinfo_path' => env('SSO_USERINFO_PATH', '/api/user'),
    'scope' => env('SSO_SCOPE', 'openid profile email'),
];
