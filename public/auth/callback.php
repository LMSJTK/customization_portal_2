<?php

require_once __DIR__ . '/../../bootstrap.php';

use CustomizationPortal\Auth\OktaAuthClient;
use CustomizationPortal\Auth\OktaJwtVerifier;

start_session_if_needed();
$config = app_config();

$expectedState = $_SESSION['pkce']['state'] ?? null;
$codeVerifier = $_SESSION['pkce']['code_verifier'] ?? null;
$nonce = $_SESSION['pkce']['nonce'] ?? null;
$pkceCreatedAt = $_SESSION['pkce']['created_at'] ?? 0;
unset($_SESSION['pkce']);

if (!$expectedState || !$codeVerifier || !$nonce) {
    $_SESSION['auth_error'] = 'Session expired or invalid login attempt. Please try again.';
    header('Location: /');
    exit;
}

if (time() - $pkceCreatedAt > 600) {
    $_SESSION['auth_error'] = 'Login attempt expired. Please try again.';
    header('Location: /');
    exit;
}

$state = $_GET['state'] ?? '';
if (!hash_equals($expectedState, $state)) {
    $_SESSION['auth_error'] = 'State mismatch. Potential CSRF detected.';
    header('Location: /');
    exit;
}

$code = $_GET['code'] ?? null;
if ($code === null) {
    $_SESSION['auth_error'] = 'Authorization code missing.';
    header('Location: /');
    exit;
}

$client = new OktaAuthClient(
    $config['okta_client_id'],
    $config['okta_issuer'],
    $config['okta_redirect_uri'],
    $config['okta_scopes']
);

try {
    $tokenResponse = $client->exchangeCodeForTokens($code, $codeVerifier);
    if (empty($tokenResponse['id_token'])) {
        throw new Exception('ID token missing in response.');
    }

    $verifier = new OktaJwtVerifier($config['okta_issuer'], $config['okta_client_id']);
    $claims = $verifier->verify($tokenResponse['id_token'], $nonce);

    $_SESSION['tokens'] = $tokenResponse;
    $_SESSION['user'] = $claims;
} catch (Throwable $exception) {
    $message = $exception->getMessage();
    if (stripos($message, 'policy evaluation failed') !== false) {
        $message .= ' Ensure the Okta user is assigned to the application and that any sign-on policies allow this flow.';
    }

    $_SESSION['auth_error'] = $message;
    $_SESSION['auth_error'] = $exception->getMessage();
}

header('Location: /');
exit;
