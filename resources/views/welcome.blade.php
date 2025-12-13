<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Raydent') }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="antialiased">
    
    <!-- Header/Navigation -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-indigo-600">
                        RAYDENT
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex space-x-8">
                    <a href="#inicio" class="text-gray-700 hover:text-indigo-600 transition">Inicio</a>
                    <a href="#servicios" class="text-gray-700 hover:text-indigo-600 transition">Servicios</a>
                    <a href="#equipo" class="text-gray-700 hover:text-indigo-600 transition">Equipo</a>
                    <a href="#eventos" class="text-gray-700 hover:text-indigo-600 transition">Eventos</a>
                    <a href="#contacto" class="text-gray-700 hover:text-indigo-600 transition">Contacto</a>
                </div>
                
                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-indigo-600 transition">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-indigo-600 transition">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                                    Register
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero-gradient text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl font-bold mb-6">
                Innovación en Investigación Científica
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-indigo-100">
                Impulsamos el futuro con tecnología de vanguardia y metodologías avanzadas
            </p>
            <a href="#contacto" class="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-indigo-50 transition">
                Comenzar Ahora
            </a>
        </div>
    </section>

    <!-- Servicios Section -->
    <section id="servicios" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Nuestras Capacidades</h2>
                <p class="text-xl text-gray-600">Ofrecemos servicios de investigación científica de clase mundial</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Servicio 1 -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition">
                    <div class="text-indigo-600 text-4xl mb-4">🧬</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Análisis Molecular</h3>
                    <p class="text-gray-600">Tecnología de última generación para análisis molecular y estudios de proteómica avanzada</p>
                </div>
                
                <!-- Servicio 2 -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition">
                    <div class="text-indigo-600 text-4xl mb-4">🔬</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Genómica Avanzada</h3>
                    <p class="text-gray-600">Secuenciación de próxima generación y estudios genéticos de alta precisión</p>
                </div>
                
                <!-- Servicio 3 -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition">
                    <div class="text-indigo-600 text-4xl mb-4">⚗️</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Química Analítica</h3>
                    <p class="text-gray-600">Cromatografía y espectrometría de masas para identificación de compuestos</p>
                </div>
                
                <!-- Servicio 4 -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition">
                    <div class="text-indigo-600 text-4xl mb-4">⚛️</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Investigación Física</h3>
                    <p class="text-gray-600">Estudios avanzados en física molecular y nanotecnología aplicada</p>
                </div>
                
                <!-- Servicio 5 -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition">
                    <div class="text-indigo-600 text-4xl mb-4">💊</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Desarrollo Farmacéutico</h3>
                    <p class="text-gray-600">Investigación y desarrollo de nuevos compuestos terapéuticos</p>
                </div>
                
                <!-- Servicio 6 -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition">
                    <div class="text-indigo-600 text-4xl mb-4">📊</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Análisis de Datos</h3>
                    <p class="text-gray-600">Bioinformática y análisis computacional de grandes volúmenes de datos</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Equipo Section -->
    <section id="equipo" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Nuestro Equipo de Expertos</h2>
                <p class="text-xl text-gray-600">Doctores e investigadores líderes en sus campos</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Miembro 1 -->
                <div class="text-center">
                    <div class="w-32 h-32 bg-indigo-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="text-4xl">👨‍⚕️</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Dr. Carlos Mendoza</h3>
                    <p class="text-indigo-600 mb-2">Director de Investigación</p>
                    <p class="text-gray-600 text-sm">PhD en Biología Molecular con más de 20 años de experiencia en genómica y biotecnología aplicada.</p>
                </div>
                
                <!-- Miembro 2 -->
                <div class="text-center">
                    <div class="w-32 h-32 bg-indigo-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="text-4xl">👩‍⚕️</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Dra. María González</h3>
                    <p class="text-indigo-600 mb-2">Jefa de Química Analítica</p>
                    <p class="text-gray-600 text-sm">Especialista en espectrometría de masas y cromatografía con múltiples publicaciones internacionales.</p>
                </div>
                
                <!-- Miembro 3 -->
                <div class="text-center">
                    <div class="w-32 h-32 bg-indigo-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <span class="text-4xl">👨‍⚕️</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Dr. Roberto Silva</h3>
                    <p class="text-indigo-600 mb-2">Director de Farmacología</p>
                    <p class="text-gray-600 text-sm">Experto en desarrollo de fármacos y estudios clínicos con certificaciones internacionales.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 hero-gradient text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-5xl font-bold mb-2">500+</div>
                    <div class="text-indigo-100">Proyectos Completados</div>
                </div>
                <div>
                    <div class="text-5xl font-bold mb-2">50+</div>
                    <div class="text-indigo-100">Investigadores Expertos</div>
                </div>
                <div>
                    <div class="text-5xl font-bold mb-2">25+</div>
                    <div class="text-indigo-100">Años de Experiencia</div>
                </div>
                <div>
                    <div class="text-5xl font-bold mb-2">200+</div>
                    <div class="text-indigo-100">Publicaciones Científicas</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="contacto" class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                ¿Listo para Impulsar tu Investigación?
            </h2>
            <p class="text-xl text-gray-600 mb-8">
                Únete a nosotros y lleva tu investigación al siguiente nivel con nuestro equipo de expertos
            </p>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Contactar Ahora
                </a>
            @endif
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <!-- Column 1 -->
                <div>
                    <h3 class="text-xl font-bold mb-4">Raydent</h3>
                    <p class="text-gray-400 text-sm">Líderes en investigación científica y desarrollo tecnológico con más de 25 años de experiencia.</p>
                </div>
                
                <!-- Column 2 -->
                <div>
                    <h4 class="font-semibold mb-4">Enlaces Rápidos</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#inicio" class="hover:text-white transition">Inicio</a></li>
                        <li><a href="#servicios" class="hover:text-white transition">Servicios</a></li>
                        <li><a href="#equipo" class="hover:text-white transition">Equipo</a></li>
                        <li><a href="#contacto" class="hover:text-white transition">Contacto</a></li>
                    </ul>
                </div>
                
                <!-- Column 3 -->
                <div>
                    <h4 class="font-semibold mb-4">Servicios</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>Análisis Molecular</li>
                        <li>Genómica Avanzada</li>
                        <li>Química Analítica</li>
                        <li>Desarrollo Farmacéutico</li>
                    </ul>
                </div>
                
                <!-- Column 4 -->
                <div>
                    <h4 class="font-semibold mb-4">Contacto</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>Av. Ciencia 123, Ciudad</li>
                        <li>+1 (555) 123-4567</li>
                        <li>info@raydent.com</li>
                        <li>Lun - Vie: 9:00 - 18:00</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} Raydent. Todos los derechos reservados.</p>
                <p class="mt-2">Innovación • Ciencia • Futuro</p>
            </div>
        </div>
    </footer>

</body>
</html>