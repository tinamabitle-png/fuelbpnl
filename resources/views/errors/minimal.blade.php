<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: "Inter", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
        }

        .container {
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
            background-repeat: repeat;
            background-position: 50% 50%;
            background:
                conic-gradient(from -296deg at 100% 59%, #aa79d5 0 133deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from -296deg at 85% 67%, #4c2b87 0 134.5deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from -296deg at 68% 77%, #aa79d5 0 137deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from -296deg at 55% 85%, #4c2b87 0 150deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from -248deg at 38% 77%, #aa79d5 0 97deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from -248deg at 15% 66%, #4c2b87 0 95deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from 207deg at 15% 66%, #aa79d5 0 84deg, #cfa5f1 0 138deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from 23deg at 85% 12%, #aa79d5 0 34deg, #cfa5f1 0 136deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from 22deg at 66% 27%, #4c2b87 0 34deg, #673ab7 0 128deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from 17deg at 50% 40%, #aa79d5 0 39deg, #cfa5f1 0 133deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from 31deg at 33% 26%, #4c2b87 0 94deg, #673ab7 0 125deg, #fff0 0 100%) 50%/198px 168px,
                conic-gradient(from -57deg at 19% 15%, #4c2b87 0 90deg, #aa79d5 0 181deg, #cfa5f1 0 217deg, #673ab7 0 360deg, #fff0 0 100%) 50%/198px 168px;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .error-card {
            width: min(92vw, 640px);
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(76, 43, 135, 0.18);
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(76, 43, 135, 0.2);
            padding: 28px 30px;
            text-align: center;
        }

        .code {
            margin: 0;
            font-size: clamp(40px, 8vw, 72px);
            line-height: 1;
            font-weight: 800;
            color: #4c2b87;
            letter-spacing: 0.02em;
        }

        .message {
            margin: 10px 0 0;
            font-size: clamp(15px, 2vw, 20px);
            line-height: 1.4;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <main class="container" role="main">
        <section class="error-card">
            <h1 class="code">@yield('code')</h1>
            <p class="message">@yield('message')</p>
        </section>
    </main>
</body>
</html>
