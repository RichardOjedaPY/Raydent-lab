<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raydent - Radiología Digital</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        raydent: {
                            DEFAULT: '#00d4ff', /* Cian Brillante */
                            dark: '#00a3cc',
                            light: '#e0f7fa'
                        },
                        darkblue: '#0a0a0a' /* Fondo casi negro */
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
        
        /* Fondo de Rayos X Simulado */
        .hero-bg {
            background-color: #000;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 212, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(0, 163, 204, 0.15) 0%, transparent 50%);
            position: relative;
        }

        /* Degradado para las tarjetas de equipo (Azul a Cian) */
        .card-gradient {
            background: linear-gradient(180deg, #2563eb 0%, #06b6d4 100%);
        }
    </style>
</head>
<body class="antialiased bg-gray-50 text-gray-800">

    <nav class="bg-black text-white py-4 shadow-lg sticky top-0 z-50 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="text-raydent text-3xl">
                    <i class="fa-solid fa-tooth fa-spin-pulse" style="--fa-animation-duration: 3s;"></i>
                </div>
                <span class="text-3xl font-bold tracking-wide text-raydent">Raydent</span>
            </div>

            <div class="hidden md:flex space-x-8 text-sm font-medium uppercase tracking-wider">
                <a href="#inicio" class="hover:text-raydent transition">Inicio</a>
                <a href="#servicios" class="hover:text-raydent transition">Servicios</a>
                <a href="#equipo" class="hover:text-raydent transition">Equipo</a>
                <a href="#contacto" class="hover:text-raydent transition">Contacto</a>
            </div>

            <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="border border-raydent text-raydent px-5 py-2 rounded-full hover:bg-raydent hover:text-black transition font-semibold text-sm">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="border-2 border-raydent text-raydent px-6 py-2 rounded-full hover:bg-raydent hover:text-black transition font-bold text-sm">
                            Log in
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <section id="inicio" class="hero-bg text-white py-32 text-center px-4">
        <div class="max-w-5xl mx-auto relative z-10">
            <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                Comprometidos con la <br>
                <span class="text-raydent">Ciencia</span>
            </h1>
            <p class="text-xl text-gray-400 mb-10 max-w-2xl mx-auto">
                Más de 25 años desarrollando soluciones científicas innovadoras y tecnología de radiología avanzada para el cuidado dental.
            </p>
            <a href="#servicios" class="bg-raydent text-black px-10 py-4 rounded-full font-bold text-lg hover:bg-white hover:scale-105 transition transform shadow-[0_0_20px_rgba(0,212,255,0.5)]">
                Nuestros Servicios
            </a>
        </div>
    </section>

    <div class="h-2 w-full bg-gradient-to-r from-blue-600 to-cyan-400"></div>

    <section id="servicios" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-800 mb-2">Nuestras Capacidades</h2>
                <p class="text-gray-500">Ofrecemos servicios de investigación científica de clase mundial</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-10 rounded-3xl shadow-xl border border-gray-100 hover:shadow-2xl transition hover:-translate-y-2 text-center group">
                    <div class="w-20 h-20 mx-auto bg-blue-500 rounded-full flex items-center justify-center text-white text-3xl mb-6 shadow-lg group-hover:scale-110 transition">
                        <i class="fa-solid fa-microscope"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Investigación Física</h3>
                    <p class="text-gray-500 leading-relaxed">Estudios avanzados en física molecular y nanotecnología aplicada.</p>
                </div>

                <div class="bg-white p-10 rounded-3xl shadow-xl border border-gray-100 hover:shadow-2xl transition hover:-translate-y-2 text-center group">
                    <div class="w-20 h-20 mx-auto bg-cyan-500 rounded-full flex items-center justify-center text-white text-3xl mb-6 shadow-lg group-hover:scale-110 transition">
                        <i class="fa-solid fa-pills"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Desarrollo Farmacéutico</h3>
                    <p class="text-gray-500 leading-relaxed">Investigación y desarrollo de nuevos compuestos terapéuticos.</p>
                </div>

                <div class="bg-white p-10 rounded-3xl shadow-xl border border-gray-100 hover:shadow-2xl transition hover:-translate-y-2 text-center group">
                    <div class="w-20 h-20 mx-auto bg-sky-500 rounded-full flex items-center justify-center text-white text-3xl mb-6 shadow-lg group-hover:scale-110 transition">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Análisis de Datos</h3>
                    <p class="text-gray-500 leading-relaxed">Bioinformática y análisis computacional de grandes volúmenes de datos.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="equipo" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-800 mb-2">Nuestro Equipo de Expertos</h2>
                <p class="text-gray-500">Doctores e investigadores líderes en sus campos</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="card-gradient rounded-2xl p-8 text-center text-white shadow-xl hover:scale-105 transition duration-300">
                    <div class="w-24 h-24 mx-auto bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-6 border-2 border-white/30">
                        <i class="fa-solid fa-user-doctor text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold">Dr. Carlos Mendoza</h3>
                    <p class="text-cyan-200 text-sm font-semibold uppercase tracking-wider mb-4">Director de Investigación</p>
                    <p class="text-white/80 text-sm border-t border-white/20 pt-4">
                        PhD en Biología Molecular con más de 20 años de experiencia en genómica.
                    </p>
                </div>

                <div class="card-gradient rounded-2xl p-8 text-center text-white shadow-xl hover:scale-105 transition duration-300">
                    <div class="w-24 h-24 mx-auto bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-6 border-2 border-white/30">
                        <i class="fa-solid fa-user-nurse text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold">Dra. María González</h3>
                    <p class="text-cyan-200 text-sm font-semibold uppercase tracking-wider mb-4">Jefa de Química</p>
                    <p class="text-white/80 text-sm border-t border-white/20 pt-4">
                        Especialista en espectrometría de masas y cromatografía internacional.
                    </p>
                </div>

                <div class="card-gradient rounded-2xl p-8 text-center text-white shadow-xl hover:scale-105 transition duration-300">
                    <div class="w-24 h-24 mx-auto bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-6 border-2 border-white/30">
                        <i class="fa-solid fa-user-tie text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold">Dr. Roberto Silva</h3>
                    <p class="text-cyan-200 text-sm font-semibold uppercase tracking-wider mb-4">Director Farmacología</p>
                    <p class="text-white/80 text-sm border-t border-white/20 pt-4">
                        Experto en desarrollo de fármacos y estudios clínicos certificados.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white text-center">
        <h2 class="text-3xl font-bold text-slate-900 mb-4">¿Listo para Impulsar tu Investigación?</h2>
        <p class="text-gray-500 mb-8 max-w-2xl mx-auto">Únete a nosotros y lleva tu investigación al siguiente nivel con nuestro equipo de expertos.</p>
        <button class="bg-raydent text-black px-8 py-3 rounded-full font-bold hover:bg-cyan-600 hover:text-white transition shadow-lg">
            Contactar Ahora
        </button>
    </section>

    <footer id="contacto" class="bg-black text-gray-400 py-16 border-t border-gray-900">
        <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-4 gap-12 text-sm">
            <div>
                <h3 class="text-raydent text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-tooth"></i> Raydent
                </h3>
                <p>Líderes en investigación científica y desarrollo tecnológico con más de 25 años de experiencia.</p>
                <div class="flex gap-4 mt-6">
                    <a href="#" class="hover:text-raydent"><i class="fa-brands fa-facebook text-xl"></i></a>
                    <a href="#" class="hover:text-raydent"><i class="fa-brands fa-instagram text-xl"></i></a>
                    <a href="#" class="hover:text-raydent"><i class="fa-brands fa-linkedin text-xl"></i></a>
                </div>
            </div>
            
            <div>
                <h4 class="text-white font-bold mb-4 uppercase">Enlaces</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-raydent transition">Inicio</a></li>
                    <li><a href="#" class="hover:text-raydent transition">Servicios</a></li>
                    <li><a href="#" class="hover:text-raydent transition">Equipo</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-4 uppercase">Contacto</h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <i class="fa-solid fa-location-dot mt-1 text-raydent"></i>
                        Av. Ciencia 123, Ciudad
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-phone text-raydent"></i>
                        +1 (555) 123-4567
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-envelope text-raydent"></i>
                        info@raydent.com
                    </li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-4 uppercase">Horario</h4>
                <p>Lunes - Viernes</p>
                <p class="text-white font-bold">9:00 AM - 6:00 PM</p>
            </div>
        </div>
        <div class="text-center mt-12 pt-8 border-t border-gray-900 text-xs">
            &copy; {{ date('Y') }} Raydent. Todos los derechos reservados.
        </div>
    </footer>

</body>
</html>