<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Returning to BlinkStudy…</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #0f1117;
            color: #e8eaed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
            padding: 24px;
        }
        .card {
            max-width: 360px;
        }
        h1 { font-size: 1.25rem; margin-bottom: 8px; }
        p { color: #9aa0a6; font-size: 0.95rem; line-height: 1.5; }
    </style>
</head>
<body>
<div class="card">
    <h1>Signing you in…</h1>
    <p>Redirecting back to the BlinkStudy app. If nothing happens, open the app and try again.</p>
</div>
<script>
(function () {
    const code = @json($code);
    const error = @json($error);
    const params = new URLSearchParams();
    if (code) {
        params.set('code', code);
    } else if (error) {
        params.set('error', error);
    } else {
        params.set('error', 'missing_code');
    }
    window.location.replace('com.blinkstudy.app:/oauth2callback?' + params.toString());
})();
</script>
</body>
</html>
