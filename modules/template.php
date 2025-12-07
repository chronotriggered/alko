<?php
function render_header(string $title = 'Alko Hinnasto'): void {
    ?><!doctype html>
    <html lang="fi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <link rel="stylesheet" href="styles.css">
        <title><?php echo htmlspecialchars($title); ?></title>
        <style>
            body { font-family: sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .pagination { margin: 20px 0; text-align: center; }
        </style>
    </head>
    <body><?php
}

function render_footer(): void {
    ?></body></html><?php
}