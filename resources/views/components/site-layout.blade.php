<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{$title}}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

</head>
<body class="bg-sky-100" style="font-family: 'Instrument Sans', sans-serif;">

    <x-site-navigation/>

    <main class="p-4">
        {{$slot}}
    </main>

    <x-site-footer/>

</body>
</html>
