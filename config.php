<?php

declare(strict_types=1);

use Respect\Validation\ValidatorBuilder as v;

class Config {
    public function __construct(
        public OIDCConfig $oidc,
        public array $providers,
    ){}

    public function validate(): void {
        v::property('oidc', v::objectType()->not(v::blank()))
        ->property('providers', v::arrayType()->not(v::blank()))
        ->assert($this);

        $this->oidc->validate();
    }
}

class OIDCConfig {
    public function __construct(
        public string $issuer_url,
        public string $redirect_url,
        public string $client_id,
        public string $client_secret
    ){}

    public function validate(): void {
        v::property('issuer_url', v::stringType()->not(v::blank())->url())
        ->property('redirect_url', v::stringType()->not(v::blank()))
        ->property('client_id', v::stringType()->not(v::blank()))
        ->property('client_secret', v::stringType()->not(v::blank()))
        ->assert($this);
    }

}

function loadConfig(): Config{
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $oidc = new OIDCConfig(
        $_ENV['OIDC_ISSUER_URL'] ?? '',
        $_ENV['OIDC_REDIRECT_URL'] ?? '',
        $_ENV['OIDC_CLIENT_ID'] ?? '',
        $_ENV['OIDC_CLIENT_SECRET'] ?? loadRemoteSecret('OIDC_CLIENT_SECRET') ?? ''
    );

    $providers = explode(',', $_ENV['PROVIDERS'] ?? '');

    $config = new Config($oidc, $providers);

    $config->validate();

    return $config;
}

function loadRemoteSecret(string $name): ?string {
    $path = $_ENV["{$name}_FILE"] ?? null;
    if ($path && file_exists($path)) {
        $content = file_get_contents($path);
        if ($content !== false && strlen($content) > 0) {
            return trim($content);
        }
    }

    return null;
}