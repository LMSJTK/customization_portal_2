# Customization Portal Prototype

This repository contains a prototype of the Customization Portal with a focus on Okta authentication. The code is
built to run on a standard LAMP stack and demonstrates how the portal will rely on Okta identity claims for user and
organization context.

## Features

- Authorization Code with PKCE flow using an Okta SPA application
- Token exchange and ID token verification performed with vanilla PHP (cURL, OpenSSL)
- Session handling that stores verified ID token claims for later use by the customization workflows
- Logout handler that clears the PHP session and redirects to the Okta logout endpoint

## Project Structure

```
config/            # Application configuration (Okta client ID, issuer, etc.)
public/            # Web root for the PHP server
  auth/            # Okta authorization endpoints (start and callback)
  index.php        # Authentication UI and status page
  logout.php       # Session termination and Okta logout
src/Auth/          # Vanilla PHP helpers for PKCE and JWT verification
bootstrap.php      # Autoloader and helpers for loading configuration + sessions
```

## Prerequisites

- PHP 8.1+ with cURL and OpenSSL extensions enabled
- An Okta developer account
- A configured Okta **SPA** application that supports the Authorization Code flow with PKCE

## Configuration

Set the following environment variables before running the PHP development server or deploying to a LAMP stack:

- `OKTA_CLIENT_ID` – The client ID of your SPA application
- `OKTA_ISSUER` – Usually `https://{yourOktaDomain}/oauth2/default`
- `OKTA_REDIRECT_URI` – e.g. `http://localhost:8000/auth/callback.php`
- `OKTA_POST_LOGOUT_REDIRECT` – e.g. `http://localhost:8000/`
- `OKTA_SCOPES` – Optional; defaults to `openid profile email`

You can alternatively edit `config/app.php`, but environment variables should be preferred for production deployments.

### Production / non-local redirect examples

When the portal is deployed behind a real domain you should register the fully-qualified HTTPS URLs that map to the
deployed PHP endpoints inside the Okta application settings. For example, if you host the project at
`https://foundational.solutions/customization_portal_3/`, configure the SPA application with:

- **Sign-in redirect URI:** `https://foundational.solutions/customization_portal_3/auth/callback.php`
- **Sign-out redirect URI:** `https://foundational.solutions/customization_portal_3/`
- **Base URI:** `https://foundational.solutions/customization_portal_3/`

These should match the values supplied to `OKTA_REDIRECT_URI` and `OKTA_POST_LOGOUT_REDIRECT` in your environment. Okta
will reject redirects that do not match the registered values exactly (including the trailing slash), so double-check
for typos when moving between local development and production.

### Troubleshooting "Policy evaluation failed" errors

If the callback page shows `Policy evaluation failed for this request, please check the policy configurations`, Okta is
blocking the authorization request due to a sign-on policy or access rule. Common fixes include:

- Verify the user attempting to sign in is **assigned to the SPA application** in Okta. Unassigned users are rejected
  by default.
- Review the **Sign On** tab for the application and the **Authentication Policy** applied to it. Update the policy so
  the relevant user, group, network zone, and device conditions allow access.
- Ensure the **Authorization Server** (e.g., `default`) has a policy rule that grants access to the requested scopes for
  the user or their groups.

After adjusting the policies, try the sign-in flow again. The callback page will now complete the token exchange.

## Local Development

```bash
export OKTA_CLIENT_ID="{clientId}"
export OKTA_ISSUER="https://dev-123456.okta.com/oauth2/default"
export OKTA_REDIRECT_URI="http://localhost:8000/auth/callback.php"
export OKTA_POST_LOGOUT_REDIRECT="http://localhost:8000/"
php -S localhost:8000 -t public
```

Visit <http://localhost:8000> to initiate the Okta sign-in flow. Upon a successful login the ID token claims are shown
on the page and stored in the PHP session for use by future customization features.
