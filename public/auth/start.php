<?php

require_once __DIR__ . '/../../bootstrap.php';

use CustomizationPortal\Auth\OktaAuthClient;
use CustomizationPortal\Auth\OktaPkce;

start_session_if_needed();
$config = app_config();

$client = new OktaAuthClient(
    $config['okta_client_id'],
    $config['okta_issuer'],
    $config['okta_redirect_uri'],
    $config['okta_scopes']
);

$state = OktaPkce::generateState();
$codeVerifier = OktaPkce::generateCodeVerifier();
$codeChallenge = OktaPkce::generateCodeChallenge($codeVerifier);
$nonce = OktaPkce::generateState(16);

$_SESSION['pkce'] = [
    'state' => $state,
    'code_verifier' => $codeVerifier,
    'nonce' => $nonce,
    'created_at' => time(),
];

$authorizeUrl = $client->buildAuthorizeUrl($state, $codeChallenge, $nonce);

header('Location: ' . $authorizeUrl);
exit;
