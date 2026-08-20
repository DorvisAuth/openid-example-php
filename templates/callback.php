<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>Welcome - Dorvis OpenID Demo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>

<body>
    <main class="container">
        <h1>Welcome!</h1>
        <p>You have been successfully authenticated via Dorvis</p>

        <h2>Authentication Details</h2>
        <p>First name: <?= htmlspecialchars($claims->given_name ?? '') ?></p>
        <p>Last name: <?= htmlspecialchars($claims->family_name ?? '') ?></p>
        <p>Personal code: <?= htmlspecialchars($claims->person_code ?? '') ?></p>

        <a href="/logout">
            <button class="secondary">Sign Out</button>
        </a>
    </main>
</body>

</html>

