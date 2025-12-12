<style>
    /* Overlay tipo modal */
    .odo-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
    }

    .odo-modal-backdrop.show {
        display: flex;
    }

    .odo-modal {
        background: #f4f7f6;
        border-radius: 10px;
        width: 95%;
        max-width: 760px;
        padding: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
    }

    .odo-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .odo-modal-header h5 {
        margin: 0;
    }

    .odo-close-btn {
        border: none;
        background: transparent;
        font-size: 20px;
        cursor: pointer;
        color: #7f8c8d;
    }

    .odo-close-btn:hover {
        color: #e74c3c;
    }

    .canvas-container {
        position: relative;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e0e0e0;
        margin-bottom: 12px;
    }

    #odoCanvas {
        display: block;
        background: radial-gradient(circle, #ffffff 0%, #f0f2f5 100%);
        cursor: default;
    }

    .odo-panel-info {
        padding: 10px 15px;
        background: #fff;
        border-left: 5px solid #3498db;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }

    .odo-panel-info .etiqueta {
        font-weight: bold;
        color: #34495e;
    }

    #lista-seleccion {
        color: #e74c3c;
        font-weight: 600;
    }

    #btn-limpiar-odo {
        background-color: #ecf0f1;
        border: 1px solid #bdc3c7;
        padding: 4px 12px;
        border-radius: 4px;
        cursor: pointer;
        color: #7f8c8d;
        transition: all 0.2s;
        font-size: 0.85rem;
    }

    #btn-limpiar-odo:hover {
        background-color: #bdc3c7;
        color: white;
    }

    .odo-modal-footer {
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .btn-outline-secondary {
        background: white;
        border: 1px solid #bdc3c7;
        color: #7f8c8d;
    }

    .btn-outline-secondary:hover {
        background: #ecf0f1;
    }

    .btn-primary {
        background: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    .mb-2 {
        margin-bottom: 0.5rem;
    }
</style>

<div id="odontograma-overlay" class="odo-modal-backdrop">
    <div class="odo-modal">
        <div class="odo-modal-header">
            <h5>Odontograma digital</h5>
            <button type="button" class="odo-close-btn" id="odo-btn-close">&times;</button>
        </div>

        <p class="mb-2" style="font-size: 0.9rem;">
            Haz clic sobre las piezas dentales para seleccionarlas o deseleccionarlas.
        </p>

        <div class="canvas-container">
            <canvas id="odoCanvas" width="700" height="400"></canvas>
        </div>

        <div class="odo-panel-info mb-2">
            <div>
                <span class="etiqueta">Piezas seleccionadas:</span>
                <span id="lista-seleccion">Ninguna</span>
            </div>
            <button type="button" id="btn-limpiar-odo">Limpiar todo</button>
        </div>

        <div class="odo-modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="odo-btn-cerrar">
                Cerrar
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="odo-btn-aplicar">
                Aplicar selección
            </button>
        </div>
    </div>
</div>

<script>
    (function() {
        const overlay = document.getElementById('odontograma-overlay');
        const btnOpen = document.getElementById('btn-open-odontograma');
        const btnClose = document.getElementById('odo-btn-close');
        const btnCerrar = document.getElementById('odo-btn-cerrar');
        const btnAplicar = document.getElementById('odo-btn-aplicar');
        const btnLimpiar = document.getElementById('btn-limpiar-odo');

        const canvas = document.getElementById('odoCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const outputModal = document.getElementById('lista-seleccion');
        const inputHidden = document.getElementById('piezas_tomografia_codigos');
        const resumenOutside = document.getElementById('piezas_tomografia_resumen');

        // Datos de dientes con tipos anatómicos
        let dientes = [
            // Cuadrante 1 (Superior Derecho)
            {id: 18, x: 50, y: 85, w: 32, h: 42, tipo: 'molar', selected: false},
            {id: 17, x: 88, y: 75, w: 32, h: 42, tipo: 'molar', selected: false},
            {id: 16, x: 126, y: 65, w: 34, h: 44, tipo: 'molar', selected: false},
            {id: 15, x: 166, y: 58, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 14, x: 200, y: 52, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 13, x: 234, y: 48, w: 26, h: 44, tipo: 'canino', selected: false},
            {id: 12, x: 266, y: 50, w: 24, h: 38, tipo: 'incisivo', selected: false},
            {id: 11, x: 296, y: 52, w: 28, h: 42, tipo: 'incisivo', selected: false},

            // Cuadrante 2 (Superior Izquierdo)
            {id: 21, x: 330, y: 52, w: 28, h: 42, tipo: 'incisivo', selected: false},
            {id: 22, x: 364, y: 50, w: 24, h: 38, tipo: 'incisivo', selected: false},
            {id: 23, x: 394, y: 48, w: 26, h: 44, tipo: 'canino', selected: false},
            {id: 24, x: 426, y: 52, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 25, x: 460, y: 58, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 26, x: 494, y: 65, w: 34, h: 44, tipo: 'molar', selected: false},
            {id: 27, x: 534, y: 75, w: 32, h: 42, tipo: 'molar', selected: false},
            {id: 28, x: 572, y: 85, w: 32, h: 42, tipo: 'molar', selected: false},

            // Cuadrante 4 (Inferior Derecho)
            {id: 48, x: 50, y: 245, w: 32, h: 42, tipo: 'molar', selected: false},
            {id: 47, x: 88, y: 255, w: 32, h: 42, tipo: 'molar', selected: false},
            {id: 46, x: 126, y: 265, w: 34, h: 44, tipo: 'molar', selected: false},
            {id: 45, x: 166, y: 272, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 44, x: 200, y: 278, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 43, x: 234, y: 278, w: 26, h: 44, tipo: 'canino', selected: false},
            {id: 42, x: 266, y: 280, w: 24, h: 38, tipo: 'incisivo', selected: false},
            {id: 41, x: 296, y: 278, w: 28, h: 42, tipo: 'incisivo', selected: false},

            // Cuadrante 3 (Inferior Izquierdo)
            {id: 31, x: 330, y: 278, w: 28, h: 42, tipo: 'incisivo', selected: false},
            {id: 32, x: 364, y: 280, w: 24, h: 38, tipo: 'incisivo', selected: false},
            {id: 33, x: 394, y: 278, w: 26, h: 44, tipo: 'canino', selected: false},
            {id: 34, x: 426, y: 278, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 35, x: 460, y: 272, w: 28, h: 38, tipo: 'premolar', selected: false},
            {id: 36, x: 494, y: 265, w: 34, h: 44, tipo: 'molar', selected: false},
            {id: 37, x: 534, y: 255, w: 32, h: 42, tipo: 'molar', selected: false},
            {id: 38, x: 572, y: 245, w: 32, h: 42, tipo: 'molar', selected: false},
        ];

        function dibujarDiente(ctx, d) {
            ctx.save();
            
            // Determinar colores según estado
            let baseColor, shadowColor, strokeColor;
            
            if (d.selected) {
                baseColor = '#3498db';
                shadowColor = 'rgba(52, 152, 219, 0.5)';
                strokeColor = '#2980b9';
            } else if (d.hover) {
                baseColor = '#ffffff';
                shadowColor = 'rgba(0, 0, 0, 0.3)';
                strokeColor = '#95a5a6';
            } else {
                baseColor = '#f8f9fa';
                shadowColor = 'rgba(0, 0, 0, 0.15)';
                strokeColor = '#bdc3c7';
            }

            // Sombra del diente
            ctx.shadowColor = shadowColor;
            ctx.shadowBlur = d.selected ? 15 : 8;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 2;

            ctx.beginPath();

            // Dibujar según tipo de diente
            switch(d.tipo) {
                case 'incisivo':
                    dibujarIncisivo(ctx, d);
                    break;
                case 'canino':
                    dibujarCanino(ctx, d);
                    break;
                case 'premolar':
                    dibujarPremolar(ctx, d);
                    break;
                case 'molar':
                    dibujarMolar(ctx, d);
                    break;
            }

            // Gradiente para simular volumen
            let gradient = ctx.createLinearGradient(d.x, d.y, d.x + d.w, d.y + d.h);
            
            if (d.selected) {
                gradient.addColorStop(0, '#5dade2');
                gradient.addColorStop(0.5, '#3498db');
                gradient.addColorStop(1, '#2874a6');
            } else {
                gradient.addColorStop(0, '#ffffff');
                gradient.addColorStop(0.3, baseColor);
                gradient.addColorStop(1, '#e8eaf0');
            }

            ctx.fillStyle = gradient;
            ctx.fill();

            // Resetear sombra para el borde
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;

            // Borde del diente
            ctx.strokeStyle = strokeColor;
            ctx.lineWidth = d.selected ? 2.5 : 1.5;
            ctx.stroke();

            // Brillo/highlight en la parte superior
            if (!d.selected) {
                ctx.beginPath();
                ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
                
                if (d.tipo === 'molar') {
                    ctx.ellipse(d.x + d.w/2, d.y + 8, d.w/3, 4, 0, 0, Math.PI * 2);
                } else if (d.tipo === 'canino') {
                    ctx.moveTo(d.x + d.w/2, d.y + 2);
                    ctx.lineTo(d.x + d.w/2 - 4, d.y + 10);
                    ctx.lineTo(d.x + d.w/2 + 4, d.y + 10);
                } else {
                    ctx.ellipse(d.x + d.w/2, d.y + 6, d.w/3.5, 3, 0, 0, Math.PI * 2);
                }
                
                ctx.fill();
            }

            // Número del diente
            ctx.fillStyle = d.selected ? '#ffffff' : '#34495e';
            ctx.font = d.selected ? 'bold 12px Arial' : '11px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(d.id, d.x + d.w/2, d.y + d.h/2);

            ctx.restore();
        }

        function dibujarIncisivo(ctx, d) {
            // Forma rectangular con esquinas redondeadas
            const radius = 3;
            ctx.moveTo(d.x + radius, d.y);
            ctx.lineTo(d.x + d.w - radius, d.y);
            ctx.quadraticCurveTo(d.x + d.w, d.y, d.x + d.w, d.y + radius);
            ctx.lineTo(d.x + d.w, d.y + d.h - radius);
            ctx.quadraticCurveTo(d.x + d.w, d.y + d.h, d.x + d.w - radius, d.y + d.h);
            ctx.lineTo(d.x + radius, d.y + d.h);
            ctx.quadraticCurveTo(d.x, d.y + d.h, d.x, d.y + d.h - radius);
            ctx.lineTo(d.x, d.y + radius);
            ctx.quadraticCurveTo(d.x, d.y, d.x + radius, d.y);
            ctx.closePath();
        }

        function dibujarCanino(ctx, d) {
            // Forma puntiaguda característica
            ctx.moveTo(d.x + d.w/2, d.y);
            ctx.lineTo(d.x + d.w, d.y + 8);
            ctx.lineTo(d.x + d.w, d.y + d.h - 6);
            ctx.quadraticCurveTo(d.x + d.w, d.y + d.h, d.x + d.w - 4, d.y + d.h);
            ctx.lineTo(d.x + 4, d.y + d.h);
            ctx.quadraticCurveTo(d.x, d.y + d.h, d.x, d.y + d.h - 6);
            ctx.lineTo(d.x, d.y + 8);
            ctx.closePath();
        }

        function dibujarPremolar(ctx, d) {
            // Forma ovalada con dos cúspides suaves
            ctx.moveTo(d.x + d.w/2, d.y);
            ctx.bezierCurveTo(d.x + d.w - 2, d.y + 2, d.x + d.w, d.y + 8, d.x + d.w, d.y + d.h/2);
            ctx.bezierCurveTo(d.x + d.w, d.y + d.h - 4, d.x + d.w - 4, d.y + d.h, d.x + d.w/2, d.y + d.h);
            ctx.bezierCurveTo(d.x + 4, d.y + d.h, d.x, d.y + d.h - 4, d.x, d.y + d.h/2);
            ctx.bezierCurveTo(d.x, d.y + 8, d.x + 2, d.y + 2, d.x + d.w/2, d.y);
            ctx.closePath();
        }

        function dibujarMolar(ctx, d) {
            // Forma cuadrada con múltiples cúspides
            const indent = 4;
            ctx.moveTo(d.x + indent, d.y);
            ctx.lineTo(d.x + d.w/2, d.y + indent);
            ctx.lineTo(d.x + d.w - indent, d.y);
            ctx.lineTo(d.x + d.w, d.y + indent);
            ctx.lineTo(d.x + d.w, d.y + d.h - indent);
            ctx.quadraticCurveTo(d.x + d.w, d.y + d.h, d.x + d.w - indent, d.y + d.h);
            ctx.lineTo(d.x + indent, d.y + d.h);
            ctx.quadraticCurveTo(d.x, d.y + d.h, d.x, d.y + d.h - indent);
            ctx.lineTo(d.x, d.y + indent);
            ctx.closePath();
        }

        function render() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Dibujar encías/arcadas con más realismo
            ctx.save();
            
            // Arcada superior
            ctx.beginPath();
            ctx.moveTo(40, 110);
            ctx.bezierCurveTo(200, 150, 450, 150, 610, 110);
            ctx.lineWidth = 20;
            ctx.strokeStyle = 'rgba(255, 182, 193, 0.15)';
            ctx.lineCap = 'round';
            ctx.stroke();

            // Línea de encía superior
            ctx.beginPath();
            ctx.moveTo(40, 100);
            ctx.bezierCurveTo(200, 135, 450, 135, 610, 100);
            ctx.lineWidth = 3;
            ctx.strokeStyle = 'rgba(231, 76, 60, 0.2)';
            ctx.stroke();

            // Arcada inferior
            ctx.beginPath();
            ctx.moveTo(40, 300);
            ctx.bezierCurveTo(200, 260, 450, 260, 610, 300);
            ctx.lineWidth = 20;
            ctx.strokeStyle = 'rgba(255, 182, 193, 0.15)';
            ctx.stroke();

            // Línea de encía inferior
            ctx.beginPath();
            ctx.moveTo(40, 310);
            ctx.bezierCurveTo(200, 275, 450, 275, 610, 310);
            ctx.lineWidth = 3;
            ctx.strokeStyle = 'rgba(231, 76, 60, 0.2)';
            ctx.stroke();

            ctx.restore();

            // Dibujar todos los dientes
            dientes.forEach(d => dibujarDiente(ctx, d));
        }

        function getDienteUnderMouse(e) {
            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            for (let d of dientes) {
                ctx.beginPath();
                switch(d.tipo) {
                    case 'incisivo': dibujarIncisivo(ctx, d); break;
                    case 'canino': dibujarCanino(ctx, d); break;
                    case 'premolar': dibujarPremolar(ctx, d); break;
                    case 'molar': dibujarMolar(ctx, d); break;
                }
                
                if (ctx.isPointInPath(mouseX, mouseY)) {
                    return d;
                }
            }
            return null;
        }

        function getSeleccionadas() {
            return dientes
                .filter(d => d.selected)
                .map(d => String(d.id));
        }

        function actualizarTexto() {
            const sel = getSeleccionadas();
            const texto = sel.length ? sel.join(', ') : 'Ninguna';

            if (outputModal) {
                outputModal.textContent = texto;
            }

            if (inputHidden) {
                inputHidden.value = sel.join(',');
            }

            if (resumenOutside) {
                if ('value' in resumenOutside) {
                    resumenOutside.value = texto;
                } else {
                    resumenOutside.textContent = texto;
                }
            }
        }

        function inicializarDesdeHidden() {
            if (!inputHidden || !inputHidden.value) {
                dientes.forEach(d => d.selected = false);
                render();
                actualizarTexto();
                return;
            }

            const codes = inputHidden.value
                .split(',')
                .map(v => v.trim())
                .filter(v => v !== '');

            dientes.forEach(d => {
                d.selected = codes.includes(String(d.id));
            });

            render();
            actualizarTexto();
        }

        canvas.addEventListener('mousemove', function(e) {
            const diente = getDienteUnderMouse(e);
            let needsRender = false;

            dientes.forEach(d => {
                if (d.hover) {
                    d.hover = false;
                    needsRender = true;
                }
            });

            if (diente) {
                diente.hover = true;
                canvas.style.cursor = 'pointer';
                needsRender = true;
            } else {
                canvas.style.cursor = 'default';
            }

            if (needsRender) render();
        });

        canvas.addEventListener('click', function(e) {
            const diente = getDienteUnderMouse(e);
            if (diente) {
                diente.selected = !diente.selected;
                render();
                actualizarTexto();
            }
        });

        btnLimpiar.addEventListener('click', () => {
            dientes.forEach(d => d.selected = false);
            render();
            actualizarTexto();
        });

        function mostrarModal() {
            overlay.classList.add('show');
            inicializarDesdeHidden();
        }

        function ocultarModal() {
            overlay.classList.remove('show');
        }

        if (btnOpen) {
            btnOpen.addEventListener('click', function(e) {
                e.preventDefault();
                mostrarModal();
            });
        }

        [btnClose, btnCerrar].forEach(b => {
            if (b) b.addEventListener('click', ocultarModal);
        });

        if (btnAplicar) {
            btnAplicar.addEventListener('click', function() {
                const seleccionadas = getSeleccionadas();

                if (window.syncPiezasTomografiaDesdeModal) {
                    window.syncPiezasTomografiaDesdeModal(seleccionadas);
                } else {
                    actualizarTexto();
                }

                ocultarModal();
            });
        }

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                ocultarModal();
            }
        });

        render();
        actualizarTexto();
    })();
</script>