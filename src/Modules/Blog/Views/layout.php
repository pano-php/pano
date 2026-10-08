<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome | Pano</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --primary: #0ea5e9;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e5e7eb;
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
        }

        .card {
            background: var(--card);
            border-radius: 18px;
            padding: 48px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 1.9rem;
            margin: 0 0 14px;
            font-weight: 600;
        }

        p {
            margin: 0 0 28px;
            color: var(--muted);
            line-height: 1.7;
        }

        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: .85rem;
            background: rgba(14,165,233,.12);
            color: var(--primary);
            margin-bottom: 24px;
            font-weight: 500;
        }

        .footer {
            margin-top: 32px;
            font-size: .85rem;
            color: var(--muted);
        }

        .footer span {
            color: var(--primary);
            font-weight: 500;
        }
    </style>
</head>
<body>

<main>
    <?php $this->section('content') ?>
</main>


</body>
</html>
