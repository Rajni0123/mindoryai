<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #e5e7eb;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            background: #111827;
            color: white;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.2s;
            margin: 0 10px 10px 0;
        }
        .button:hover {
            background: #374151;
        }
        .back-link {
            display: inline-block;
            color: #6b7280;
            text-decoration: none;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.2s;
            margin: 0 10px 10px 0;
        }
        .back-link:hover {
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">
            The page you're looking for doesn't exist.
        </p>
        <a href="{{ url('/') }}" class="button">
            Go to Homepage
        </a>
        <button onclick="history.back()" class="back-link">
            Go Back
        </button>
    </div>
</body>
</html>
