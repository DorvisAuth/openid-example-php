<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;

try {
    $config = loadConfig();
} catch (\Exception $e) {
    serveError("Failed to retrieve config: " . $e->getMessage());
    return;
}

if (!is_dir(__DIR__ . '/cache')) {
    mkdir(__DIR__ . '/cache', 0700);
}
$discoveryCacheFile = __DIR__ . '/cache/discovery-' . hash('sha256', $config->oidc->issuer_url) . '.json';

try {
    $client = new Client(['verify' => \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath()]);
    if (is_file($discoveryCacheFile) && (time() - filemtime($discoveryCacheFile)) < 21600) {
        $wellKnownResponse = file_get_contents($discoveryCacheFile);
    } else {
        $res = $client->request('GET', "{$config->oidc->issuer_url}/.well-known/openid-configuration");
        $wellKnownResponse = (string) $res->getBody();
        file_put_contents($discoveryCacheFile, $wellKnownResponse);
        chmod($discoveryCacheFile, 0600);
    }
    $data = json_decode($wellKnownResponse, true);
    if (!is_array($data)) {
        throw new \RuntimeException("Discovery document was not JSON");
    }
} catch (\Throwable $e) {
    serveError("Failed to load OIDC discovery document: " . $e->getMessage());
    return;
}

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => $config->oidc->client_id,    
    'clientSecret'            => $config->oidc->client_secret,
    'redirectUri'             => $config->oidc->redirect_url,
    'urlAuthorize'            => $data['authorization_endpoint'],
    'urlAccessToken'          => $data['token_endpoint'],
    'urlResourceOwnerDetails' => $data['userinfo_endpoint'],
    'scopes' => ['openid', 'profile'],
    'scopeSeparator' => ' ',
], [
    'httpClient' => $client,
]);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routes = [
    '/' => fn() => login($config, $provider),
    '/login' => fn() => login($config, $provider),
    '/callback' => fn() => callback($provider, $config, $client, $data),
    '/logout' => fn() => logout($config, $data),
];

if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}

if (array_key_exists($requestUri, $routes)) {
    $routes[$requestUri]();
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}

function login(Config $config, GenericProvider $provider){
    if (empty($_SESSION['user'])) {
        session_unset();
        session_regenerate_id(true);
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth2state'] = $state;

    $nonce = bin2hex(random_bytes(16));
    $_SESSION['oidc_nonce'] = $nonce;

    $codeVerifier = bin2hex(random_bytes(32));
    $_SESSION['oidc_pkce_verifier'] = $codeVerifier;
    $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

    $authParams = [
        'state'                 => $state,
        'nonce'                 => $nonce,
        'code_challenge'        => $codeChallenge,
        'code_challenge_method' => 'S256',
    ];

    $defaultAuthUrl = $provider->getAuthorizationUrl($authParams);

    $providers = [];
    foreach ($config->providers as $acrValue) {
        $providers[] = [
            'label' => $acrValue,
            'url'   => $provider->getAuthorizationUrl($authParams + ['acr_values' => "idp:{$acrValue}"]),
        ];
    }

    include __DIR__ . '/templates/login.php';
}

function callback(GenericProvider $provider, Config $config, Client $client, array $data){
    if (!empty($_GET['error'])){
        $message = $_GET['error'];
        serveError($message);
        return;
    }

    if (empty($_GET['code'])){
        serveError("Missing authorization code in callback");
        return;
    }

    if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || !hash_equals($_SESSION['oauth2state'], $_GET['state'])){
        serveError("Invalid state parameter");
        return;
    }
    unset($_SESSION['oauth2state']);

    try {
        $jwks_uri = $data['jwks_uri'];
        $jwksResponse = $client->request('GET', $jwks_uri);
        $jwksData = json_decode((string) $jwksResponse->getBody(), true);
        $tokenData = JWK::parseKeySet($jwksData);
    } catch (\Exception $e) {
        serveError("Failed to load signing keys: " . $e->getMessage());
        return;
    }

    if (empty($_SESSION['oidc_pkce_verifier'])) {
        serveError("Missing PKCE verifier");
        return;
    }
    $provider->setPkceCode($_SESSION['oidc_pkce_verifier']);
    unset($_SESSION['oidc_pkce_verifier']);

    try {
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    } catch (\Exception $e) {
        serveError("Failed to retrieve access token: " . $e->getMessage());
        return;
    }

    $rawIdToken = $accessToken->getValues()['id_token'] ?? '';
    if (empty($rawIdToken)){
        serveError("Failed to retrieve id_token");
        return;
    }

    try {
        $claims = JWT::decode($rawIdToken, $tokenData);
    } catch (\Exception $e) {
        serveError("Failed to verify id_token: " . $e->getMessage());
        return;
    }

    if ($claims->iss !== $config->oidc->issuer_url || !in_array($config->oidc->client_id, (array) $claims->aud, true)) {
        serveError("Issuer or audience mismatch");
        return;
    }

    if (empty($_SESSION['oidc_nonce']) || empty($claims->nonce) || !hash_equals($_SESSION['oidc_nonce'], $claims->nonce)) {
        serveError("Invalid nonce");
        return;
    }
    unset($_SESSION['oidc_nonce']);

    session_regenerate_id(true);
    $_SESSION['user'] = (array) $claims;
    $_SESSION['id_token'] = $rawIdToken;

    include __DIR__ . '/templates/callback.php';
}

function logout(Config $config, array $data){
    $rawIdToken = $_SESSION['id_token'] ?? '';
    $redirUri = baseUrl();
    $logoutUrl = $redirUri;
    if (!empty($data['end_session_endpoint']) && !empty($rawIdToken)) {
        $logoutUrl = $data['end_session_endpoint'] . "?id_token_hint=" . urlencode($rawIdToken) . "&post_logout_redirect_uri=" . urlencode($redirUri);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();

    header("Location: {$logoutUrl}");
    exit;
}

function serveError(string $message){
    $loginUrl = baseUrl();
    http_response_code(400);
    include __DIR__ . '/templates/error.php';
}

function baseUrl(){
    $scheme = "http";
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off'){
        $scheme = "https";
    }
    return $scheme . "://" . $_SERVER['HTTP_HOST'];
}