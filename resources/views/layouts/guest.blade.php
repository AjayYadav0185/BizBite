<!DOCTYPE html>
<html lang="en-IN">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-bg font-sans text-ink antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
