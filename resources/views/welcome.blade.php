<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raydent - Procesos laboratoriales de calidad</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="apple-touch-icon" href="https://raydentradiologia.com.py/RAYDENT-LOGO.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            overflow-x: hidden;
        }

        /* Top Bar */
        .top-bar {
            background: #00d4ff;
            color: #000000;
            padding: 0.8rem 0;
            font-size: 0.9rem;
        }

        .top-bar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .contact-info {
            display: flex;
            gap: 2rem;
        }

        .contact-info span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: #000000;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: #00d4ff;
            transform: translateY(-3px);
        }

        /* Header */
        header {
            background: rgb(0, 0, 0);
            color: #00d4ff;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #1e3c72;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #00d4ff;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .auth-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .login-btn {
            color: #ffffff;
            border: 2px solid #00d4ff;
        }

        .login-btn:hover {
            background: #00d4ff;
            color: #000!important;
        }

        .register-btn {
            background: #ffffff;
            color: #00d4ff!important;
        }

        .register-btn:hover {
            background: #ffffff;
            color: #000;
        }

        /* Menú hamburguesa */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
            z-index: 1002;
            position: relative;
        }

        .menu-toggle span {
            width: 25px;
            height: 3px;
            background: #ffffff;
            transition: all 0.3s;
        }

        /* Slider */
        .slider {
            position: relative;
            height: 600px;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s;
            background-size: cover;
            background-position: center;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-1 {
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.85), #00d4ff),
                url('https://www.dpdo.ca/wp-content/uploads/2017/05/dental-xrays.jpg');
        }

        .slide-2 {
            background: linear-gradient(135deg, rgba(42, 82, 152, 0.85), #00d4ff),
                url('https://www.cdepa.com/wp-content/uploads/2020/02/xray-1024x641.jpg');
        }

        .slide-3 {
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.9), rgba(42, 82, 152, 0.8)),
                url('https://thumbs.dreamstime.com/b/detailed-dental-ray-image-human-teeth-showing-molars-incisors-detailed-dental-ray-image-human-teeth-showing-molars-376783515.jpg');
        }

        .slide-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            width: 90%;
            max-width: 900px;
        }

        .slide-content h2 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            animation: slideInUp 1s ease;
        }

        .slide-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            animation: slideInUp 1s ease 0.2s backwards;
        }

        .slide-btn {
            display: inline-block;
            background: #00d4ff;
            color: #1e3c72;
            padding: 1rem 3rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s;
            animation: slideInUp 1s ease 0.4s backwards;
        }

        .slide-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.6);
        }

        .slider-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }

        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .slider-dot.active {
            background: #00d4ff;
            width: 40px;
            border-radius: 6px;
        }

        /* Features Section */
        .features {
            padding: 100px 2rem;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: #1e3c72;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 3rem;
            font-size: 1.1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2a5298, #00d4ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .feature-card h3 {
            color: #1e3c72;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        /* Video Section */
        .video-section {
            padding: 100px 2rem;
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.95), rgba(0, 212, 255, 0.9)),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 400"><rect fill="%231e3c72" width="1200" height="400"/><circle cx="300" cy="200" r="150" fill="rgba(0,212,255,0.1)"/><circle cx="900" cy="200" r="100" fill="rgba(0,212,255,0.1)"/></svg>');
            color: white;
            text-align: center;
        }

        .video-container {
            max-width: 900px;
            margin: 2rem auto 0;
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .video-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .play-button {
            width: 100px;
            height: 100px;
            background: rgba(0, 212, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .play-button:hover {
            transform: scale(1.1);
            background: #00d4ff;
        }

        .play-button i {
            font-size: 2rem;
            color: white;
            margin-left: 5px;
        }

        /* Team Section */
        .team-section {
            padding: 100px 2rem;
            background: white;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .team-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .team-image {
            width: 100%;
            height: 350px;
            background: linear-gradient(135deg, #2a5298, #00d4ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: white;
        }

        .team-info {
            padding: 2rem;
            text-align: center;
        }

        .team-info h4 {
            color: #1e3c72;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .team-info .role {
            color: #00d4ff;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .team-info p {
            color: #666;
            line-height: 1.6;
        }

        /* Stats with Progress Bars */
        .stats-section {
            padding: 100px 2rem;
            background: #f8f9fa;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .stat-bar {
            text-align: center;
        }

        .stat-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2a5298, #00d4ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .stat-bar h4 {
            color: #1e3c72;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .progress-container {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2a5298, #00d4ff);
            border-radius: 10px;
            transition: width 2s ease;
            width: 0;
        }

        .percentage {
            font-size: 2rem;
            font-weight: bold;
            color: #00d4ff;
            margin-top: 0.5rem;
        }

        /* Events Gallery */
        .events-section {
            padding: 100px 2rem;
            background: white;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .event-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }

        .event-date {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #00d4ff;
            color: #1e3c72;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
        }

        .event-day {
            font-size: 2rem;
            display: block;
        }

        .event-month {
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .event-info {
            padding: 2rem;
        }

        .event-info h4 {
            color: #1e3c72;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .event-meta {
            display: flex;
            gap: 1.5rem;
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .event-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Map Section */
        .map-section {
            padding: 100px 2rem;
            background: #f8f9fa;
        }

        .map-container {
            width: 100%;
            height: 500px;
            background: linear-gradient(135deg, #2a5298, #00d4ff);
            border-radius: 15px;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .map-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            background: rgba(30, 60, 114, 0.9);
            padding: 3rem;
            border-radius: 15px;
        }

        .map-overlay h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .map-overlay p {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        /* Stats Section */
        .stats {
            padding: 100px 2rem;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
        }

        .stats-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #00d4ff;
        }

        .stat-item p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 2rem;
            background: #f8f9fa;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            color: #1e3c72;
            margin-bottom: 1.5rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto 2.5rem;
        }

        .cta-button {
            display: inline-block;
            background: #00d4ff;
            color: #1e3c72;
            padding: 1rem 2.5rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.6);
        }

        /* Footer */
        footer {
            background: #000000;
            color: white;
            padding: 4rem 2rem 2rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-column h3 {
            color: #00d4ff;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .footer-column p,
        .footer-column li {
            color: #ccc;
            line-height: 1.8;
            margin-bottom: 0.5rem;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
            display: block;
            margin-bottom: 0.5rem;
        }

        .footer-column a:hover {
            color: #00d4ff;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .footer-social a:hover {
            background: #00d4ff;
            transform: translateY(-3px);
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #999;
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Overlay cuando el menú está abierto */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .slide-content h2 {
                font-size: 2rem;
            }

            .top-bar-content {
                flex-direction: column;
                gap: 1rem;
            }

            .contact-info {
                flex-direction: column;
                gap: 0.5rem;
            }

            /* Header responsive */
            .menu-toggle {
                display: flex;
            }

            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                background: #000;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 2rem;
                transition: right 0.3s ease;
                z-index: 1001;
                padding: 2rem;
            }

            .nav-links.active {
                right: 0;
            }

            .nav-links li {
                width: 100%;
                text-align: center;
            }

            .nav-links a {
                display: block;
                padding: 1rem;
                font-size: 1.2rem;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
                margin: 0 auto;
            }

            .auth-btn {
                text-align: center;
                width: 100%;
            }

            /* Animación del menú hamburguesa */
            .menu-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }

            .menu-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .menu-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -6px);
            }
        }
    </style>
</head>

<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="contact-info">
                <span><a href="https://wa.me/+595973665779" style="color: #000000; text-decoration: none;"><i class="fas fa-phone"></i> +595-973 665 779</a></span>
                <span><i class="fas fa-envelope"></i> info@raydentradiologia.com.py</span>
            </div>
            <div class="social-links">
                <a href="https://www.facebook.com/raydentradiologia"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/raydentradiologia511/"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <nav>
            <div class="logo">
                <img src="/RAYDENT-LOGO.png"  style="margin: 0.1em; height: 75px;">
            </div>
            
            <!-- Menú hamburguesa -->
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#equipo">Equipo</a></li>
                <li><a href="#contacto">Contacto</a></li>
                <li>
                    @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="auth-btn login-btn">
                            Panel de Control
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="auth-btn login-btn">
                            Log in
                        </a>
                    @endauth
                @endif
                </li>
            </ul>
            
            <!-- Overlay para cerrar el menú -->
            <div class="overlay" id="overlay"></div>
        </nav>
    </header>

    <!-- Slider -->
    <section class="slider" id="inicio">
        <div class="slide slide-1 active">
            <div class="slide-content">
                <h2>Tomografía computarizada Cone beam</h2>
                <p>Imágenes dentales 3D de alta precisión. Diagnóstico detallado, menor radiación y máxima comodidad para el paciente.</p>
                <a href="#contacto" class="slide-btn">Comenzar Ahora</a>
            </div>
        </div>
        <div class="slide slide-2">
            <div class="slide-content">
                <h2>Radiografías computarizadas</h2>
                    <h3>Panorámica <br>
                    Teleradiografias <br>
                    ATM <br>
                    Escaneamiento intraoral <br>
                    Modelos 3D<br><br></h3>
                   
                <p>Tecnología 3D avanzada para diagnósticos precisos y tratamientos dentales totalmente personalizados.</p>
                <a href="#contacto" class="slide-btn">Conocer Más</a>
            </div>
        </div>
        <div class="slide slide-3">
            <div class="slide-content">
                <h2>Horarios de atención especial!!</h2>
                <p><b>Lunes a viernes 8:00 a 12:00hs 14:00 a 18:00hs</b></p>
                <p><b>Sábados 8:00 a 12:00hs 13:30 a 17:30hs</b></p>
                <a href="#contacto" class="slide-btn">Nuestros Servicios</a>
            </div>
        </div>
        <div class="slider-controls">
            <span class="slider-dot active" data-slide="0"></span>
            <span class="slider-dot" data-slide="1"></span>
            <span class="slider-dot" data-slide="2"></span>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="servicios">
        <div class="container">
            <h2 class="section-title">Nuestras Capacidades</h2>
            <p class="section-subtitle">Servicios laboratoriales computarizadas de alta precisión</p>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-teeth-open"></i></div>
                    <h3>Radiografía panorámica</h3>
                    <p>Vista completa de tus dientes y mandíbula para un diagnóstico general.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-skull"></i></div>
                    <h3>Telerradiografías</h3>
                    <p>Radiografías laterales para analizar el crecimiento facial y planificar ortodoncia.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-flask"></i></div>
                    <h3>ATM</h3>
                    <p>Imágenes especializadas para diagnosticar problemas en la articulación de la mandíbula.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-teeth-open"></i></div>
                    <h3>Escaneo intraoral</h3>
                    <p>Cámara digital que captura una réplica 3D precisa de tu boca.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-tooth"></i></div>
                    <h3>Modelos 3D</h3>
                    <p>Reconstrucciones digitales exactas para planificar tu tratamiento de forma virtual.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Análisis de Datos</h3>
                    <p>Bioinformática y análisis computacional de grandes volúmenes de datos</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Section -->
    <!--<section class="video-section">
        <div class="container">
            <h2 class="section-title" style="color: white;">Conozca Nuestras Instalaciones</h2>
            <p class="section-subtitle" style="color: rgba(255,255,255,0.9);">Un recorrido por nuestros laboratorios
                de investigación</p>
            <div class="video-container">
                <div class="video-placeholder">
                    <div class="play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>-->

    <!-- Team Section -->
    <section class="team-section" id="equipo">
        <div class="container">
            <h2 class="section-title">Nuestro Equipo de Expertos</h2>
            <p class="section-subtitle">Doctores e investigadores líderes en sus campos</p>
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-image"><i class="fas fa-user-md"></i></div>
                    <div class="team-info">
                        <h4>Dra. Lissandry Rivas</h4>
                        <p class="role"> Odontóloga</p>
                        <p>Especialista en implantes dentales. Rg. Profesional 8.173</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="team-image"><i class="fas fa-user-md"></i></div>
                    <div class="team-info">
                        <h4>Dra. Maria Isabel Acuña</h4>
                        <p class="role">Odontóloga</p>
                        <p>Especialista en ortodoncia correctiva y ortopedia facial. Rg. Profesional 8.131
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="contacto">
        <div class="container">
            <h2>¿Listo para ofrecer el mejor servicio a tus pacientes?</h2>
            <p>Únete a nosotros y lleva tu clínica al siguiente nivel con nuestro equipo de expertos que te asesorarán en los mejores servicios que podamos ofrecerte.</p>
            <a href="https://wa.me/+595973665779" class="cta-button">Contactar Ahora</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Raydent</h3>
                <p>Líder en diagnóstico computarizado y tratamientos dentales.</p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/raydentradiologia" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/raydentradiologia511/" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Enlaces Rápidos</h3>
                <ul>
                    <li><a href="#inicio">Inicio</a></li>
                    <li><a href="#servicios">Servicios</a></li> 
                    <li><a href="#equipo">Equipo</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Servicios</h3>
                <ul>
                    <li><a href="#">Radiografías Panorámicas</a></li>
                    <li><a href="#">Teleradiografías</a></li>
                    <li><a href="#">ATM</a></li>
                    <li><a href="#">Escaneaminto Intraoral</a></li>
                    <li><a href="#">Modelos 3d</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> Av. Cesar Gionotti c/ Calle Cnel Bogado - Hernandarias · Edificio Dinámica al costado de IPS</p>
                <p><a href="https://wa.me/+595973665779" target="_blank" style="color: #ccc; text-decoration: none;"><i class="fas fa-phone"></i> +595 973 665 779</a></p>
                <p><i class="fas fa-envelope"></i> info@raydent.com.py</p>
                <p><i class="fas fa-clock"></i> Lunes a viernes 8:00 a 12:00hs 14:00 a 18:00hs</p>
                <p><i class="fas fa-clock"></i> Sábados 8:00 a 12:00hs 13:30 a 17:30hs</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Raydent Radiología. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Mostrar año actual en el footer
        document.querySelector('.footer-bottom p').innerHTML = `&copy; ${new Date().getFullYear()} Raydent Radiología. Todos los derechos reservados.`;

        // Funcionalidad del menú móvil
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        const overlay = document.getElementById('overlay');

        function toggleMobileMenu() {
            menuToggle.classList.toggle('active');
            navLinks.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
        }

        menuToggle.addEventListener('click', toggleMobileMenu);
        overlay.addEventListener('click', toggleMobileMenu);

        // Cerrar el menú al hacer clic en un enlace
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768 && navLinks.classList.contains('active')) {
                    toggleMobileMenu();
                }
            });
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Slider functionality
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');
        let currentSlide = 0;

        function showSlide(n) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            currentSlide = (n + slides.length) % slides.length;

            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }

        // Auto slide
        setInterval(() => {
            showSlide(currentSlide + 1);
        }, 5000);

        // Dot click events
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                showSlide(parseInt(this.getAttribute('data-slide')));
            });
        });
    </script>
</body>

</html>