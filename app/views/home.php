<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $Title ?></title>
</head>

<body>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>

    <h2>Response Metadata</h2>
    <ul>
        <li><strong>Status Code:</strong> <?= htmlspecialchars($response['statusCode']) ?></li>
        <li><strong>Execution Time:</strong> <?= htmlspecialchars($response['executionTime']) ?> seconds</li>
        <li><strong>Error:</strong> <?= htmlspecialchars($response['error'] ?? 'None') ?></li>
    </ul>

    <h2>Raw Body Output</h2>
    <pre><?= htmlspecialchars($response['body']) ?></pre>
</body>

</html>