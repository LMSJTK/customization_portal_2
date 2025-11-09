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
