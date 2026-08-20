# Dorvis OpenID Connect Demo (PHP)

Simple demonstration application built on vanilla PHP showing how to integrate [Dorvis](https://dorvis.eu) authentication hub with OpenID Connect.

## Quick Start

### Prerequisites

- **PHP** (version 8.1 or higher)
- **Composer** (version 2.0 or higher)
- **Dorvis client credentials** (see setup below)

### 1. Setup Dorvis Client

1. Visit the [Dorvis platform](https://dorvis.eu) and create a new client
2. Configure your client with these settings:
   - **Redirect URI**: `http://localhost:3000/callback`
   - **Grant Types**: Authorization Code
   - **Scopes**: `openid`, `profile` (default)
3. Save your **Client ID** and **Client Secret** for the next step

### 2. Environment configuration

Copy the environment configuration from example.

   ```bash
   cp .env.example .env
   ```

`.env.example` contains working configuration against Demo environment. See [Configuration options](#configuration-options) to configure for production use.

### 3. Install dependencies

   ```bash
   composer install
   ```

### 4. Run the application

   ```bash
   php -S localhost:3000 router.php
   ```

The application will start at <http://localhost:3000>

## How to Use

### Standard Authentication Flow

1. Click **"Sign in via Dorvis Platform"** on the home page
2. You'll be redirected to Dorvis where you can choose your identity provider
3. Complete authentication with your chosen provider
4. You'll be redirected back to see your authentication details

### Direct Provider Selection (Demo Feature)

1. Click any of the **"Sign in with [Provider]"** buttons
2. You'll be taken directly to that provider, bypassing Dorvis selection
3. This demonstrates the `acr_values` parameter functionality

## Configuration Options

| Variable | Description | Demo Value | Production Value |
|----------|-------------|------------|------------------|
| `OIDC_ISSUER_URL` | Dorvis OIDC server base URL | `https://demo.dorvis.eu/oidc` | `https://dorvis.eu/oidc` |
| `OIDC_CLIENT_ID` | Your Dorvis client ID | `dorvis_demo_post` | `your-client-id` |
| `OIDC_CLIENT_SECRET` | Your Dorvis client secret | `dorvis_demo_secret` | `your-secret` |
| `OIDC_REDIRECT_URL` | Callback URL after authentication (must be whitelisted in Dorvis) | `http://localhost:3000/callback` | `https://your-domain.com/callback` |
| `ACR_VALUES` | Comma-separated identity providers<br>to show on login page | `Swedbank,Seb,Luminor,`<br>`Citadele,eParaksts,`<br>`eParaksts-mobile,Smartid` | `Swedbank,Seb,Luminor,`<br>`Citadele,eParaksts,`<br>`eParaksts-mobile,Smartid` |

## Learn More

- [More about Dorvis](https://dorvis.eu)
- [OpenID Connect Specification](https://openid.net/connect/)