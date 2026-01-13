<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - Welcome</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'ui-sans-serif', 'system-ui'],
                    },
                },
            },
        }
    </script>

    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .animate-fade-in {
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen gradient-bg relative overflow-hidden">
        <!-- Decorative elements -->
        <div class="absolute top-0 left-0 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-float"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-float" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-0 left-1/2 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-float" style="animation-delay: 4s;"></div>

        <!-- Header -->
        <header class="relative z-10">
            <nav class="container mx-auto px-6 py-8">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                        </svg>
                        <span class="text-2xl font-bold text-white">{{ config('app.name', 'Laravel') }}</span>
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="glass-effect text-white px-6 py-2.5 rounded-lg font-medium hover:bg-white hover:bg-opacity-20 transition duration-300">
                            Dashboard
                        </a>
                    @else
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('login') }}" class="text-white font-medium hover:text-purple-100 transition duration-300">
                                Login
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="glass-effect text-white px-6 py-2.5 rounded-lg font-medium hover:bg-white hover:bg-opacity-20 transition duration-300">
                                    Register
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 container mx-auto px-6 py-16">
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <!-- Left Column - Content -->
                    <div class="text-white space-y-8 animate-fade-in">
                        <h1 class="text-5xl md:text-6xl font-bold leading-tight">
                            Welcome to<br>
                            <span class="text-yellow-300">{{ config('app.name') }}</span>
                        </h1>

                        <p class="text-xl text-purple-100 leading-relaxed">
                            A powerful administration platform built with Laravel. Manage your application with ease and efficiency.
                        </p>

                        <!-- Features -->
                        <div class="grid grid-cols-2 gap-4 pt-8">
                            <div class="flex items-start space-x-3">
                                <svg class="w-6 h-6 text-yellow-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="font-semibold">Secure Access</h3>
                                    <p class="text-sm text-purple-100">Protected authentication</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <svg class="w-6 h-6 text-yellow-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="font-semibold">Easy Management</h3>
                                    <p class="text-sm text-purple-100">Intuitive interface</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <svg class="w-6 h-6 text-yellow-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="font-semibold">Fast Performance</h3>
                                    <p class="text-sm text-purple-100">Optimized speed</p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <svg class="w-6 h-6 text-yellow-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="font-semibold">Responsive Design</h3>
                                    <p class="text-sm text-purple-100">Works everywhere</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Quick Login Card -->
                    <div class="glass-effect rounded-2xl p-8 shadow-2xl animate-fade-in" style="animation-delay: 0.2s;">
                        <div class="bg-white rounded-xl p-8">
                            <div class="text-center mb-8">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-800">Admin Access</h2>
                                <p class="text-gray-600 mt-2">Sign in to your account</p>
                            </div>

                            @guest
                                <div class="space-y-4">
                                    <a href="{{ route('login') }}" class="block w-full text-center px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition duration-300 shadow-lg hover:shadow-xl">
                                        Sign In
                                    </a>

                                    <!-- @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="block w-full text-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition duration-300">
                                            Create New Account
                                        </a>
                                    @endif -->

                                    <div class="relative my-6">
                                        <div class="absolute inset-0 flex items-center">
                                            <div class="w-full border-t border-gray-300"></div>
                                        </div>
                                        <div class="relative flex justify-center text-sm">
                                            <span class="px-2 bg-white text-gray-500">Or continue with</span>
                                        </div>
                                    </div>

                                    <div class="text-center text-sm text-gray-600">
                                        <p>Need help? <a href="#" class="text-purple-600 hover:text-purple-700 font-medium">Contact Support</a></p>
                                    </div>
                                </div>
                            @else
                                <div class="text-center space-y-4">
                                    <div class="flex items-center justify-center space-x-2 text-green-600 mb-4">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="font-semibold">You're logged in!</span>
                                    </div>

                                    <p class="text-gray-600">Welcome back, <span class="font-semibold text-purple-600">
                                        @if(Auth::user()->first_name && Auth::user()->last_name)
                                            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                                        @else
                                            {{ Auth::user()->name }}
                                        @endif
                                    </span></p>

                                    <a href="{{ route('dashboard') }}" class="block w-full text-center px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition duration-300 shadow-lg hover:shadow-xl">
                                        Go to Dashboard
                                    </a>
                                </div>
                            @endguest
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 container mx-auto px-6 py-8 mt-16">
            <div class="text-center text-white text-sm">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p class="mt-2 text-purple-100">Powered by Laravel {{ app()->version() }}</p>
            </div>
        </footer>
    </div>
</body>
</html>
