<?php

require_once __DIR__ . '/../bootstrap.php';

use CustomizationPortal\Auth\OktaAuthClient;

start_session_if_needed();
$config = app_config();

$client = new OktaAuthClient(
    $config['okta_client_id'],
    $config['okta_issuer'],
    $config['okta_redirect_uri'],
    $config['okta_scopes']
);

$user = $_SESSION['user'] ?? null;
$error = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customization Portal - Okta Authentication</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f6fa;
        }

        main {
            max-width: 640px;
            width: 100%;
        }

        .card {
            padding: 2rem;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }

        .token-details {
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 320px;
            overflow: auto;
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 12px;
        }

        .notice {
            padding: 1rem;
            border-radius: 12px;
            background: #fef3c7;
            border: 1px solid #facc15;
            color: #92400e;
        }

        .error {
            padding: 1rem;
            border-radius: 12px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1>Customization Portal Authentication</h1>
        <p>This prototype uses Okta's Authorization Code with PKCE flow to authenticate users. Configure your
            Okta client ID, issuer, and redirect URIs in <code>config/app.php</code> or environment variables before
            deploying to a LAMP server.</p>

        <?php if ($error): ?>
            <div class="error">
                <strong>Authentication Error:</strong>
                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$user): ?>
            <p class="notice">You are not signed in. Use the button below to begin the Okta login flow.</p>
            <form method="post" action="<?= htmlspecialchars(app_url('auth/start.php'), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Sign in with Okta</button>
            </form>
        <?php else: ?>
            <article>
                <header>
                    <h2>Signed in as <?= htmlspecialchars($user['email'] ?? $user['preferred_username'] ?? 'Unknown user', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p>Okta ID: <code><?= htmlspecialchars($user['sub'] ?? 'n/a', ENT_QUOTES, 'UTF-8') ?></code></p>
                </header>
                <p>The ID token claims below will be used to associate created customizations with the authenticated
                    organization without storing user data directly.</p>
                <details>
                    <summary>View ID Token Claims</summary>
                    <pre class="token-details"><?= htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
                </details>
                <?php if (!empty($_SESSION['tokens']['access_token'] ?? null)): ?>
                    <details>
                        <summary>Raw Token Response</summary>
                        <pre class="token-details"><?= htmlspecialchars(json_encode($_SESSION['tokens'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
                    </details>
                <?php endif; ?>
                <footer>
                    <a href="<?= htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" role="button" class="contrast">Sign out</a>
                </footer>
            </article>
        <?php endif; ?>

        <hr>
        <h2>Local Development</h2>
        <ol>
            <li>Create a <strong>SPA</strong> application in Okta and enable Authorization Code with PKCE.</li>
            <li>Set the login redirect URI to <code><?= htmlspecialchars($config['okta_redirect_uri'], ENT_QUOTES, 'UTF-8') ?></code>.</li>
            <li>Set the logout redirect URI to <code><?= htmlspecialchars($config['okta_post_logout_redirect'], ENT_QUOTES, 'UTF-8') ?></code>.</li>
            <li>Export the required environment variables before running <code>php -S localhost:8000 -t public</code>.</li>
        </ol>
    </section>
</main>
</body>
</html>
