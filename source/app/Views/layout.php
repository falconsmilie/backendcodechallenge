<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $title ?? 'Commit Viewer' ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/digitallytailored/classless@latest/classless.min.css">
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        ul { list-style-type: none; padding-left: 0; }
        li { margin: 0.5rem 0; }
    </style>
    <script>
        (function () {
            const savedScheme = localStorage.getItem('color-scheme');
            const scheme = savedScheme || 'dark';
            document.documentElement.setAttribute('color-scheme', scheme);
        })();
    </script>
</head>
<body>
    <button onclick="toggleTheme()" class="outline" style="float: right;margin-top: 36px;"><small>Light / Dark</small></button>
    <script>
        function toggleTheme() {
            const current = document.documentElement.getAttribute('color-scheme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('color-scheme', next);
            localStorage.setItem('color-scheme', next);
        }
    </script>
    <?= $content ?? '' ?>
</body>
</html>
