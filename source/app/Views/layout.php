<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $title ?? 'Commit Viewer' ?></title>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        h1, h2 { margin-top: 2rem; }
        ul { list-style-type: none; padding-left: 0; }
        li { margin: 0.5rem 0; }
    </style>
</head>
<body>
    <?= $content ?? '' ?>
</body>
</html>
