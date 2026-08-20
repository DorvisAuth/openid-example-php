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
        <h1>Oops! Something went wrong</h1>

        <p>
            <?php if ($message){
                echo htmlspecialchars($message);
            } else {
                echo "An unexpected error occurred during authentication.";
            }
            ?>
        </p>

        <a href="<?= htmlspecialchars($loginUrl) ?>">
            <button>Try Again</button>
        </a>
    </main>
</body>

</html>