<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            padding: 2rem;
            background: #0f172a;
            color: #f8fafc;
        }

        .card {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #334155;
        }

        pre {
            background: #020617;
            padding: 1rem;
            border-radius: 6px;
            color: #38bdf8;
            overflow-x: auto;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            margin-bottom: 0.5rem;
        }

        strong {
            color: #94a3b8;
        }

        .success {
            color: #4ade80;
        }

        .error {
            color: #f87171;
        }
    </style>
</head>

<body>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>

    <div class="card">
        <h2>Request Metadata</h2>
        <ul>
            <li><strong>Status Code:</strong> <span class="success"><?= htmlspecialchars($response['statusCode']) ?></span></li>
            <li><strong>Execution Time:</strong> <?= htmlspecialchars(number_format($response['executionTime'], 4)) ?> seconds</li>
            <li><strong>Content Type:</strong> <?= htmlspecialchars($response['contentType'] ?? 'N/A') ?></li>
            <li><strong>Download Size:</strong> <?= htmlspecialchars($response['size']) ?> bytes</li>
            <li><strong>Error:</strong> <span class="<?= $response['error'] ? 'error' : 'success' ?>"><?= htmlspecialchars($response['error'] ?? 'None') ?></span></li>
        </ul>
    </div>

    <div class="card">
        <h2>Raw Payload Output</h2>
        <pre><?= htmlspecialchars($response['body']) ?></pre>
    </div>
</body>

</html>