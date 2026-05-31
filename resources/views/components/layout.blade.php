<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]">

    @include('components.navbar')

    {{ $slot }}

</body>
</html>