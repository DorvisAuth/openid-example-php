<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>Dorvis OpenID Demo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>

<body>
    <main class="container">
        <h1>Dorvis OpenID Demo</h1>
        <p>This example demonstrates how to integrate OpenID Connect authentication with the <a href="https://dorvis.eu"
                target="_blank">Dorvis</a> authentication hub in a
            PHP application.</p>

        <section>
            <h2>Standard Authentication Flow</h2>
            <p>Let Dorvis present you with available identity providers to choose from:</p>
            <p>
                <a href="<?= htmlspecialchars($defaultAuthUrl) ?>">
                    <button class="contrast">Sign in via Dorvis Platform</button>
                </a>
            </p>
            <p>
                <small>You'll be redirected to Dorvis where you can select your preferred identity provider.</small>
            </p>
        </section>

        <section>
            <h2>Direct Provider Selection</h2>
            <p>You can also directly specify a provider to bypass provider selection in Dorvis:</p>
            <?php foreach ($providers as $item): ?>
                <p>
                    <a href="<?= htmlspecialchars($item['url']) ?>">
                        <button class="outline">Sign in with <?= htmlspecialchars($item['label']) ?></button>
                    </a>
                </p>
            <?php endforeach; ?>
                <small>These buttons demonstrate direct provider selection using the <code>acr_values</code>
                        parameter.</small>
        </section>
    </main>
</body>

</html>