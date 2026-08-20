<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config.php';

final class SmokeTest extends TestCase
{
    public function testAppBoots(): void
    {
        putenv('OIDC_ISSUER_URL=https://demo.dorvis.eu/oidc');
        putenv('OIDC_REDIRECT_URL=http://localhost:3000/callback');
        putenv('OIDC_CLIENT_ID=dorvis_demo_post');
        putenv('OIDC_CLIENT_SECRET=dorvis_demo_secret');
        putenv('ACR_VALUES=idp:Swedbank,idp:Seb');

        loadConfig();

        $source = file_get_contents(__DIR__ . '/../index.php');
        preg_match_all("/'(\/[a-zA-Z\/]*)' => fn\(\)/", $source, $matches);

        $this->assertEqualsCanonicalizing(
            ['/', '/login', '/callback', '/logout'],
            $matches[1]
        );
    }
}
