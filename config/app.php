<?php

return [
    'okta_client_id' => getenv('OKTA_CLIENT_ID') ?: '{clientId}',
    'okta_issuer' => getenv('OKTA_ISSUER') ?: 'https://{yourOktaDomain}/oauth2/default',
    'okta_redirect_uri' => getenv('OKTA_REDIRECT_URI') ?: 'http://localhost:8000/auth/callback.php',
    'okta_scopes' => array_filter(array_map('trim', explode(' ', getenv('OKTA_SCOPES') ?: 'openid profile email'))),
    'okta_post_logout_redirect' => getenv('OKTA_POST_LOGOUT_REDIRECT') ?: 'http://localhost:8000/',
    'session_cookie_lifetime' => (int) (getenv('SESSION_COOKIE_LIFETIME') ?: 3600),
    'app_base_path' => getenv('APP_BASE_PATH') ?: '/',
];
