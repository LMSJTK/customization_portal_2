<?php

require_once __DIR__ . '/../bootstrap.php';

use CustomizationPortal\Auth\OktaAuthClient;

start_session_if_needed();
$config = app_config();

$idToken = $_SESSION['tokens']['id_token'] ?? null;

$_SESSION = [];
session_destroy();

if ($idToken) {
    $client = new OktaAuthClient(
        $config['okta_client_id'],
        $config['okta_issuer'],
        $config['okta_redirect_uri'],
        $config['okta_scopes']
    );

    $logoutUrl = $client->buildLogoutUrl($idToken, $config['okta_post_logout_redirect']);
    header('Location: ' . $logoutUrl);
    exit;
}

header('Location: ' . app_url());
exit;
