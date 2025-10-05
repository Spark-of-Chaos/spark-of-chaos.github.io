<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'Spark of Chaos'))</title>
    
    <!-- Vite CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    @stack('styles')
    @stack('scripts-head')
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="fixed z-10 w-full top-0 left-0 flex justify-center items-center p-4 text-white bg-white/20 backdrop-blur-md shadow-lg">
        <div class="flex items-center gap-3">
            <a href="/" class="flex items-center">
                <img src="{{ Vite::asset('resources/images/logo.svg') }}" alt="Spark of Chaos logo" class="h-8 w-8 mr-2">
            </a>
            <a class="px-3 py-2 rounded hover:bg-orange-200/80 transition-colors text-black font-semibold" href="/#games">Games</a>
            <a class="px-3 py-2 rounded hover:bg-orange-200/80 transition-colors text-black font-semibold" href="/#team">Team</a>
            <a class="px-3 py-2 rounded hover:bg-orange-200/80 transition-colors text-black font-semibold" href="/#contact">Contact</a>
            <a href="https://discord.gg/2RtsJUMprB" target="_blank" class="ml-2">
                <svg class="w-7 h-7 text-black hover:text-orange-400 transition-colors"  role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" id="Discord--Streamline-Simple-Icons" height="24" width="24">
                <path d="M20.317 4.3698a19.7913 19.7913 0 0 0 -4.8851 -1.5152 0.0741 0.0741 0 0 0 -0.0785 0.0371c-0.211 0.3753 -0.4447 0.8648 -0.6083 1.2495 -1.8447 -0.2762 -3.68 -0.2762 -5.4868 0 -0.1636 -0.3933 -0.4058 -0.8742 -0.6177 -1.2495a0.077 0.077 0 0 0 -0.0785 -0.037 19.7363 19.7363 0 0 0 -4.8852 1.515 0.0699 0.0699 0 0 0 -0.0321 0.0277C0.5334 9.0458 -0.319 13.5799 0.0992 18.0578a0.0824 0.0824 0 0 0 0.0312 0.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a0.0777 0.0777 0 0 0 0.0842 -0.0276c0.4616 -0.6304 0.8731 -1.2952 1.226 -1.9942a0.076 0.076 0 0 0 -0.0416 -0.1057c-0.6528 -0.2476 -1.2743 -0.5495 -1.8722 -0.8923a0.077 0.077 0 0 1 -0.0076 -0.1277c0.1258 -0.0943 0.2517 -0.1923 0.3718 -0.2914a0.0743 0.0743 0 0 1 0.0776 -0.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a0.0739 0.0739 0 0 1 0.0785 0.0095c0.1202 0.099 0.246 0.1981 0.3728 0.2924a0.077 0.077 0 0 1 -0.0066 0.1276 12.2986 12.2986 0 0 1 -1.873 0.8914 0.0766 0.0766 0 0 0 -0.0407 0.1067c0.3604 0.698 0.7719 1.3628 1.225 1.9932a0.076 0.076 0 0 0 0.0842 0.0286c1.961 -0.6067 3.9495 -1.5219 6.0023 -3.0294a0.077 0.077 0 0 0 0.0313 -0.0552c0.5004 -5.177 -0.8382 -9.6739 -3.5485 -13.6604a0.061 0.061 0 0 0 -0.0312 -0.0286zM8.02 15.3312c-1.1825 0 -2.1569 -1.0857 -2.1569 -2.419 0 -1.3332 0.9555 -2.4189 2.157 -2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332 -0.9555 2.4189 -2.1569 2.4189zm7.9748 0c-1.1825 0 -2.1569 -1.0857 -2.1569 -2.419 0 -1.3332 0.9554 -2.4189 2.1569 -2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332 -0.946 2.4189 -2.1568 2.4189Z" fill="#000000" stroke-width="1"></path>
                </svg>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white" id="footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>