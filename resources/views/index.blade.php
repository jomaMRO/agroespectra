<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra</title>

    <!-- bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- letras -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- estilos CSS -->
    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">

    <!-- Estilos extra para que la home quede moderna (no rompe su CSS existente) -->
    <style>
        :root{
            --brand-1:#2b2a8f;
            --brand-2:#7b2cbf;
            --soft:#f6f7fb;
        }

        body{
            font-family: "Courier Prime", monospace;
            background: var(--soft);
        }

        /* Hero */
        .hero-wrap{
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(0,0,0,.06);
            background: radial-gradient(1200px 500px at 10% 10%, rgba(123,44,191,.18), transparent 60%),
                        radial-gradient(900px 500px at 90% 20%, rgba(43,42,143,.18), transparent 60%),
                        linear-gradient(180deg, #ffffff, #f6f7fb);
        }
        .hero-wrap::before{
            content:"";
            position:absolute;
            inset:-120px -120px auto auto;
            width:420px;
            height:420px;
            border-radius:999px;
            background: rgba(123,44,191,.12);
            filter: blur(0px);
        }
        .hero-wrap::after{
            content:"";
            position:absolute;
            inset:auto auto -140px -140px;
            width:520px;
            height:520px;
            border-radius:999px;
            background: rgba(43,42,143,.12);
        }

        .hero-card{
            border: 1px solid rgba(0,0,0,.06);
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0,0,0,.08);
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(6px);
        }

        .hero-badge{
            display:inline-flex;
            gap:.5rem;
            align-items:center;
            padding:.35rem .75rem;
            border-radius:999px;
            border:1px solid rgba(0,0,0,.08);
            background:#fff;
            font-size:.85rem;
        }

        .brand-title{
            font-weight: 700;
            letter-spacing: .2px;
        }

       

        /* Cards accesos */
        .action-card{
            border: 1px solid rgba(0,0,0,.06);
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(0,0,0,.06);
            transition: transform .15s ease, box-shadow .15s ease;
            background:#fff;
            height: 100%;
        }
        .action-card:hover{
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(0,0,0,.10);
        }

        .icon-pill{
            width:46px;height:46px;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
            background: rgba(123,44,191,.10);
            border:1px solid rgba(123,44,191,.18);
        }

        .btn-brand{
            border:0;
            border-radius: 14px;
            padding: .7rem 1rem;
            background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
        }
        .btn-brand:hover{
            opacity: .95;
        }

        .mini-muted{
            color: rgba(0,0,0,.65);
            font-size: .92rem;
            line-height: 1.35rem;
        }

        .footer-soft{
            border-top: 1px solid rgba(0,0,0,.06);
            background: #fff;
        }

        
    </style>
    <style class="darkreader">
  .brand-gradient{
    background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
  }
</style>

</head>

<body>

    <!-- cabecera (se mantiene su lógica) -->
    <header class="header">
        <div class="container logo-nav-container">
            <a href="{{ url('/') }}" class="logo">
                Agro<span class="spectra">Espectra</span><i class="bi bi-activity"></i>
            </a>

            <span class="menu-icon" id="menuIcon">
                <i class="bi bi-list" id="icon-open"></i>
                <i class="bi bi-x" id="icon-close"></i>
            </span>

            <nav class="navigation">
                <ul>
                    <li><a href="{{ url('/buscadorFTIR') }}">Buscador</a></li>
                    <li><a href="{{ url('/comparar') }}">Comparar</a></li>
                    <li><a href="{{ url('/subir') }}">Subir</a></li>

                    @if (Route::has('login'))
                        @auth
                            <li><a href="{{ url('/panel') }}">Dashboard</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Logout</button>
                                </form>
                            </li>
                        @else
                            <li><a href="{{ route('login') }}">Login</a></li>
                            @if (Route::has('register'))
                                <li><a href="{{ route('register') }}">Register</a></li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero-wrap py-5">
        <div class="container position-relative" style="z-index:2;">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-6">
                    <div class="hero-card p-4 p-md-5">
                        <div class="d-flex flex-column align-items-start gap-2 mb-3">
                            <span class="hero-badge">
                                <i class="bi bi-activity"></i>
                                Análisis de similitud
                            </span>
                            <span class="hero-badge">
                                <i class="bi bi-database"></i>
                                Biblioteca FTIR
                            </span>
                        </div>

                        <h1 class="brand-title mb-3">
  <span
    class="brand-gradient"
    style="
      background: linear-gradient(90deg, var(--brand-1), var(--brand-2)) !important;
      -webkit-background-clip: text !important;
      background-clip: text !important;
      color: transparent !important;
      -webkit-text-fill-color: transparent !important;
      display: inline-block !important;
    "
  >
    AgroEspectra
  </span>
  FTIR BD
</h1>

                        <p class="mini-muted mb-4">
                            Este sitio constituye una base de datos colaborativa de espectros de Transformada de Fourier Infrarroja (FTIR)
                            de productos agroquímicos y aditivos asociados a sus formulaciones. Adicionalmente integra herramientas de búsqueda y 
                            comparación por espectro,permitiendo contrastar un espectro cargado por el usuario frente a los registrados mediante un cálculo de diferencia punto a punto.
                        </p>

                        <p class="mini-muted mb-4">
                        Asimismo, incorpora detección de picos para apoyar la interpretación del espectro y la determinación de grupos funcionales. La biblioteca se fortalece de forma continua con nuevos aportes de los usuarios, mejorando su utilidad con el tiempo.                        </p>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url('/buscadorFTIR') }}"
                               class="btn btn-brand text-white"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               title="Buscar en biblioteca.">
                                <i class="bi bi-search me-2"></i> Banco de Datos FTIR
                            </a>

                            <a href="{{ url('/comparar') }}"
                               class="btn btn-outline-dark"
                               data-bs-toggle="tooltip"
                               data-bs-placement="top"
                               title="Comparación FTIR.">
                                <i class="bi bi-intersect me-2"></i> Comparar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card action-card p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="icon-pill">
                                <i class="bi bi-activity fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-1">Accesos rápidos</h4>
                                <div class="mini-muted">Atajos a las herramientas principales.</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Card 1 -->
                            <div class="col-12 col-md-6">
                                <div class="action-card p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="icon-pill">
                                            <i class="bi bi-search fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Buscador</div>
                                            <div class="mini-muted mb-2">Buscar en biblioteca.</div>
                                            <a href="{{ url('/buscadorFTIR') }}"
                                               class="btn btn-sm btn-brand text-white w-100"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Encuentre espectros por nombre y visualice el gráfico.
                                                        Consulte la biblioteca y descargue el CSV del espectro.
                                                        Búsqueda rápida de espectros disponibles en la base.">
                                                Ir al buscador
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2 -->
                            <div class="col-12 col-md-6">
                                <div class="action-card p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="icon-pill">
                                            <i class="bi bi-intersect fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Comparar</div>
                                            <div class="mini-muted mb-2">Comparación FTIR.</div>
                                            <a href="{{ url('/comparar') }}"
                                               class="btn btn-sm btn-brand text-white w-100"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Superponga dos espectros y calcule similitud punto a punto.
                                                        Compare un espectro con otro y obtenga distancia y coincidencias.
                                                        Evalúe similitud entre muestras y biblioteca en segundos.">
                                                Abrir comparación
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3 -->
                            <div class="col-12 col-md-6">
                                <div class="action-card p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="icon-pill">
                                            <i class="bi bi-cloud-upload fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Subir archivo</div>
                                            <div class="mini-muted mb-2">Agregar a biblioteca.</div>
                                            <a href="{{ url('/subir') }}"
                                               class="btn btn-sm btn-brand text-white w-100"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Cargue un CSV FTIR para consulta y análisis.
                                                        Suba un espectro y regístrelo en la base de datos.
                                                        Incorpore nuevos espectros para ampliar la biblioteca.">
                                                Subir FTIR
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4 -->
                            <div class="col-12 col-md-6">
                                <div class="action-card p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="icon-pill">
                                            <i class="bi bi-lightning-charge fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Coincidencia vectorial</div>
                                            <div class="mini-muted mb-2">Buscar por FTIR.</div>
                                            <a href="{{ url('/identificar') }}"
                                               class="btn btn-sm btn-brand text-white w-100"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Cargue un espectro y encuentre los más similares en la biblioteca.
Busque coincidencias por forma espectral, no por nombre.
Compare una muestra contra la base y obtenga el Top de similitud.">
                                                Abrir temporal
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 5 (full width) -->
                            <div class="col-12">
                                <div class="action-card p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="icon-pill">
                                            <i class="bi bi-graph-up fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold mb-1">Identificar picos</div>
                                            <div class="mini-muted mb-2">Picos y grupos funcionales.</div>
                                            <a href="{{ url('/identificar-grupos') }}"
                                               class="btn btn-sm btn-brand text-white"
                                               style="border-radius:14px;"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Detecte picos relevantes y obtenga apoyo para interpretar grupos funcionales del espectro FTIR.

Resalte picos principales y consulte referencias para interpretación del espectro.

Herramienta de ayuda para identificar picos y orientar la asignación de grupos funcionales.">
                                                Ir a identificación
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- row -->
                    </div><!-- card -->
                </div><!-- col -->
            </div><!-- row -->
        </div><!-- container -->
    </section>

    <!-- Pie de página -->
    <footer class="footer-soft d-flex flex-column align-items-center justify-content-center py-3">
        <div class="container text-center">
            <span class="text-muted small">
                © 2026 Agroespectra. Todos los derechos reservados. Para consultas o soporte técnico, contáctenos en support@ftirbd.com
            </span>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Menú -->
    <script>
        document.getElementById('menuIcon').addEventListener('click', function() {
            var nav = document.querySelector('.navigation ul');
            var iconOpen = document.getElementById('icon-open');
            var iconClose = document.getElementById('icon-close');
            if (nav.style.display === 'block') {
                nav.style.display = 'none';
                iconOpen.style.display = 'block';
                iconClose.style.display = 'none';
            } else {
                nav.style.display = 'block';
                iconOpen.style.display = 'none';
                iconClose.style.display = 'block';
            }
        });
    </script>

    <!-- ✅ Tooltips (placeholders en cada botón) -->
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    </script>

</body>
</html>
