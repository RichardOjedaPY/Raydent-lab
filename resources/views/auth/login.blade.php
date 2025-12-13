<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Raydent</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        raydent: {
                            DEFAULT: '#00d4ff', /* Cian Brillante */
                            dark: '#00a3cc',
                        },
                        darkbg: '#0a0a0a'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Fondo Oscuro con efecto sutil */
        .login-bg {
            background-color: #050505;
            background-image: 
                radial-gradient(at top left, rgba(0, 212, 255, 0.1) 0%, transparent 40%),
                radial-gradient(at bottom right, rgba(0, 163, 204, 0.1) 0%, transparent 40%);
        }
        
        /* Efecto Glassmorphism para la tarjeta */
        .glass-card {
            background: rgba(20, 20, 25, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex flex-col items-center justify-center text-gray-200">

    <div class="absolute top-6 left-6">
        <a href="/" class="text-gray-400 hover:text-raydent transition flex items-center gap-2 text-sm font-medium">
            <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
        </a>
    </div>

    <div class="w-full max-w-md px-6">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-900 border border-raydent/30 text-raydent mb-4 shadow-[0_0_15px_rgba(0,212,255,0.3)]">
                <i class="fa-solid fa-tooth text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-white">Bienvenido</h2>
            <p class="text-gray-500 mt-2 text-sm">Ingrese sus credenciales para acceder al sistema</p>
        </div>

        <div class="glass-card rounded-2xl p-8 shadow-2xl">
            
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Correo Electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-envelope text-gray-500"></i>
                        </div>
                        <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" 
                            class="w-full pl-10 pr-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-raydent focus:border-raydent text-white placeholder-gray-500 transition shadow-inner"
                            placeholder="ejemplo@raydent.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-400 text-sm" />
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-2">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-500"></i>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="w-full pl-10 pr-4 py-3 bg-gray-900/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-raydent focus:border-raydent text-white placeholder-gray-500 transition shadow-inner"
                            placeholder="••••••••">
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-400 text-sm" />
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded bg-gray-800 border-gray-700 text-raydent focus:ring-raydent" name="remember">
                        <span class="ml-2 text-sm text-gray-400 hover:text-gray-300 cursor-pointer">Recordarme</span>
                    </label>

                   {{-- ESTO ESTÁ OCULTO TEMPORALMENTE
                    @if (Route::has('password.request'))
                        <a class="text-sm text-raydent hover:text-cyan-300 transition underline-offset-4 hover:underline" href="{{ route('password.request') }}">
                            ¿Olvidó su contraseña?
                        </a>
                    @endif
                    --}}
                </div>

                <button type="submit" class="w-full bg-raydent hover:bg-cyan-400 text-black font-bold py-3 px-4 rounded-lg shadow-[0_0_20px_rgba(0,212,255,0.4)] hover:shadow-[0_0_30px_rgba(0,212,255,0.6)] transition duration-300 transform hover:-translate-y-0.5">
                    Iniciar Sesión
                </button>
            </form>
        </div>

        <p class="text-center text-gray-600 text-xs mt-8">
            &copy; {{ date('Y') }} Raydent. Sistema seguro.
        </p>
    </div>

</body>
</html>