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
            <canvas id="odoCanvas" width="600" height="350"></canvas>
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
        if (!canvas) return; // seguridad
        const ctx = canvas.getContext('2d');

        const outputModal = document.getElementById('lista-seleccion');
        const inputHidden = document.getElementById('piezas_codigos');
        const resumenOutside = document.getElementById('piezas_codigos_resumen');

        // ---- datos de dientes (igual que tu ejemplo) ----
        let dientes = [{
                id: 18,
                x: 40,
                y: 80,
                w: 28,
                h: 35,
                selected: false
            },
            {
                id: 17,
                x: 75,
                y: 70,
                w: 28,
                h: 35,
                selected: false
            },
            {
                id: 16,
                x: 110,
                y: 60,
                w: 30,
                h: 38,
                selected: false
            },
            {
                id: 15,
                x: 145,
                y: 55,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 14,
                x: 175,
                y: 50,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 13,
                x: 205,
                y: 50,
                w: 25,
                h: 38,
                selected: false
            },
            {
                id: 12,
                x: 235,
                y: 55,
                w: 22,
                h: 35,
                selected: false
            },
            {
                id: 11,
                x: 265,
                y: 60,
                w: 30,
                h: 40,
                selected: false
            },

            {
                id: 21,
                x: 305,
                y: 60,
                w: 30,
                h: 40,
                selected: false
            },
            {
                id: 22,
                x: 340,
                y: 55,
                w: 22,
                h: 35,
                selected: false
            },
            {
                id: 23,
                x: 370,
                y: 50,
                w: 25,
                h: 38,
                selected: false
            },
            {
                id: 24,
                x: 400,
                y: 50,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 25,
                x: 430,
                y: 55,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 26,
                x: 460,
                y: 60,
                w: 30,
                h: 38,
                selected: false
            },
            {
                id: 27,
                x: 495,
                y: 70,
                w: 28,
                h: 35,
                selected: false
            },
            {
                id: 28,
                x: 530,
                y: 80,
                w: 28,
                h: 35,
                selected: false
            },

            {
                id: 48,
                x: 40,
                y: 220,
                w: 28,
                h: 35,
                selected: false
            },
            {
                id: 47,
                x: 75,
                y: 230,
                w: 28,
                h: 35,
                selected: false
            },
            {
                id: 46,
                x: 110,
                y: 240,
                w: 30,
                h: 38,
                selected: false
            },
            {
                id: 45,
                x: 145,
                y: 245,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 44,
                x: 175,
                y: 250,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 43,
                x: 205,
                y: 250,
                w: 25,
                h: 38,
                selected: false
            },
            {
                id: 42,
                x: 235,
                y: 245,
                w: 22,
                h: 35,
                selected: false
            },
            {
                id: 41,
                x: 265,
                y: 240,
                w: 30,
                h: 40,
                selected: false
            },

            {
                id: 31,
                x: 305,
                y: 240,
                w: 30,
                h: 40,
                selected: false
            },
            {
                id: 32,
                x: 340,
                y: 245,
                w: 22,
                h: 35,
                selected: false
            },
            {
                id: 33,
                x: 370,
                y: 250,
                w: 25,
                h: 38,
                selected: false
            },
            {
                id: 34,
                x: 400,
                y: 250,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 35,
                x: 430,
                y: 245,
                w: 25,
                h: 35,
                selected: false
            },
            {
                id: 36,
                x: 460,
                y: 240,
                w: 30,
                h: 38,
                selected: false
            },
            {
                id: 37,
                x: 495,
                y: 230,
                w: 28,
                h: 35,
                selected: false
            },
            {
                id: 38,
                x: 530,
                y: 220,
                w: 28,
                h: 35,
                selected: false
            },
        ];

        function dibujarFormaDiente(ctx, d) {
            ctx.beginPath();
            ctx.moveTo(d.x, d.y + 5);
            ctx.bezierCurveTo(d.x + 5, d.y - 5, d.x + d.w - 5, d.y - 5, d.x + d.w, d.y + 5);
            ctx.lineTo(d.x + d.w - 2, d.y + d.h - 10);
            ctx.bezierCurveTo(d.x + d.w / 2 + 5, d.y + d.h + 5, d.x + d.w / 2 - 5, d.y + d.h + 5, d.x + 2, d.y + d
                .h - 10);
            ctx.closePath();
        }

        function render() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.beginPath();
            ctx.moveTo(30, 100);
            ctx.bezierCurveTo(300, 150, 300, 150, 570, 100);
            ctx.moveTo(30, 280);
            ctx.bezierCurveTo(300, 230, 300, 230, 570, 280);
            ctx.lineWidth = 15;
            ctx.strokeStyle = "rgba(231, 76, 60, 0.1)";
            ctx.lineCap = "round";
            ctx.stroke();

            dientes.forEach(d => {
                dibujarFormaDiente(ctx, d);

                let gradient = ctx.createLinearGradient(d.x, d.y, d.x + d.w, d.y + d.h);

                if (d.selected) {
                    gradient.addColorStop(0, '#3498db');
                    gradient.addColorStop(1, '#2980b9');
                    ctx.shadowColor = "rgba(41, 128, 185, 0.6)";
                    ctx.shadowBlur = 15;
                } else if (d.hover) {
                    gradient.addColorStop(0, '#ffffff');
                    gradient.addColorStop(1, '#dfe6e9');
                    ctx.shadowColor = "rgba(0,0,0,0.3)";
                    ctx.shadowBlur = 10;
                } else {
                    gradient.addColorStop(0, '#ffffff');
                    gradient.addColorStop(1, '#ecf0f1');
                    ctx.shadowColor = "rgba(0,0,0,0.2)";
                    ctx.shadowBlur = 3;
                }

                ctx.fillStyle = gradient;
                ctx.fill();
                ctx.lineWidth = 1;
                ctx.strokeStyle = d.selected ? '#1a5276' : '#bdc3c7';
                ctx.stroke();

                ctx.shadowBlur = 0;
                ctx.fillStyle = d.selected ? 'white' : '#7f8c8d';
                ctx.font = "11px Arial";
                ctx.textAlign = "center";
                ctx.fillText(d.id, d.x + d.w / 2, d.y + d.h / 2 + 4);
            });
        }

        function getDienteUnderMouse(e) {
            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            for (let d of dientes) {
                dibujarFormaDiente(ctx, d);
                if (ctx.isPointInPath(mouseX, mouseY)) {
                    return d;
                }
            }
            return null;
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
        // Ejemplo dentro del modal
        function guardarOdontograma() {
            const seleccionadas = obtenerPiezasSeleccionadas(); // tu lógica actual
            if (window.syncPiezasTomografiaDesdeModal) {
                window.syncPiezasTomografiaDesdeModal(seleccionadas);
            }
            $('#odontogramaModal').modal('hide');
        }

        function actualizarTexto() {
            const sel = dientes.filter(d => d.selected).map(d => d.id);
            const texto = sel.length ? sel.join(', ') : 'Ninguna';

            if (outputModal) outputModal.textContent = texto;
            if (inputHidden) inputHidden.value = sel.join(',');
            if (resumenOutside) resumenOutside.textContent = texto || 'Ninguna';
        }

        // Inicializar selección desde el hidden (para edición)
        function inicializarDesdeHidden() {
            if (!inputHidden) return;
            const value = (inputHidden.value || '').trim();
            if (!value) return;

            const codes = value.split(',').map(v => v.trim());
            dientes.forEach(d => {
                d.selected = codes.includes(String(d.id));
            });
            render();
            actualizarTexto();
        }

        // Botones de abrir/cerrar
        function mostrarModal() {
            overlay.classList.add('show');
            render();
            actualizarTexto();
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
            if (b) b.addEventListener('click', function() {
                ocultarModal();
            });
        });
        if (btnAplicar) {
            btnAplicar.addEventListener('click', function() {
                // ya se sincroniza en tiempo real con el hidden
                ocultarModal();
            });
        }

        // Si clic fuera del modal, cerrar
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                ocultarModal();
            }
        });

        inicializarDesdeHidden();
    })();
</script>
