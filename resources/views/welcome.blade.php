<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }} "  >
    <head>
        <meta charset="utf-8">
        
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Dental Clinic</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.png" type="image/png">
        <link rel="alternate icon" href="/favicon.ico">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css" rel="stylesheet">


        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

                
    </head>
    <body class="antialiased">
    <div id="app"></div>

    </body>
</html>
