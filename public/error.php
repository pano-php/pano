<?php

/** @var \Pano\Kernel\BaseException $error */
$error = $exception;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pano | Error</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
            --text: #0f172a;
            --muted: #64748b;
            --border: #e5e7eb;
            --code-bg: #f1f5f9;
        }

        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc, #e2e8f0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
        }

        .card {
            background: var(--card);
            border-radius: 18px;
            padding: 48px;
            max-width: 540px;
            width: 100%;
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: .85rem;
            background: var(--danger-bg);
            color: var(--danger);
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        h1 {
            font-size: 1.85rem;
            margin: 0 0 12px;
            font-weight: 600;
        }

        p {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.98rem;
        }

        .error-box {
            background: var(--code-bg);
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 24px;
            text-align: left;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.88rem;
            color: #334155;
            word-break: break-word;
        }

        .footer {
            margin-top: 28px;
            font-size: .85rem;
            color: var(--muted);
        }

        .footer a {
            color: var(--danger);
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<main style="max-width: 35vw;min-width: 35vw;">
    <div class="card">
        <div class="logo">⚠️</div>

        <div class="badge"><?= $code ?> Error</div>

        <h1><?= $error->getMessage() ?></h1>

        <?php if ($debug): ?>
        <p>
            <?= $error->getFile() ?>(<?= $error->getLine() ?>)
        </p>
        <div class="error-box">
            <?php foreach ($error->getTrace() as $key => $err): ?>
            <div>
                <?= $key + 1 . '. ' . $err['class'] . ' @ ' . $err['function'] . ' : ' . $err['line'] ?>
            </div>
            <br/>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            Something Went Wrong.
        <?php endif ?>

        <div class="footer">
            Back to <span><a href="<?= url('/') ?>">Home</a></span>
        </div>
    </div>
</main>

</body>
</html>
