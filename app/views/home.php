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
            font-size: 0.9rem;
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

        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .badge-success {
            background: #166534;
            color: #4ade80;
        }

        .badge-error {
            background: #991b1b;
            color: #f87171;
        }
    </style>
</head>

<body>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>

    <div class="card">
        <h2>Request Telemetry</h2>
        <ul>
            <li>
                <strong>Status Code:</strong>
                <span class="badge <?= $response->isSuccess() ? 'badge-success' : 'badge-error' ?>">
                    <?= htmlspecialchars($response->getStatusCode()) ?>
                </span>
            </li>
            <li><strong>Execution Time:</strong> <?= htmlspecialchars(number_format($response->getExecutionTime(), 4)) ?> seconds</li>
            <li><strong>Format Detected:</strong> <?= $response->isJson() ? 'Parsed JSON Array' : 'Raw String / Text' ?></li>
            <li>
                <strong>Errors:</strong>
                <span class="<?= $response->getError() ? 'badge-error' : 'badge-success' ?>">
                    <?= htmlspecialchars($response->getError() ?? 'None') ?>
                </span>
            </li>
        </ul>
    </div>

    <div class="card">
        <h2>Parsed Data Output</h2>
        <?php if ($response->isJson()): ?>
            <pre><?= htmlspecialchars(json_encode($response->json(), JSON_PRETTY_PRINT)) ?></pre>
        <?php else: ?>
            <pre><?= htmlspecialchars($response->getBody()) ?></pre>
        <?php endif; ?>
    </div>
</body>

</html>