<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra - Subir Archivo</title>
    <!-- bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- letras -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="{{ asset('css/subir.css') }}">
</head>

<body>
    <!-- cabezera -->
    <header class="header">
        <div class="container logo-nav-container">
            <a href="{{ url('/') }}" class="logo">
                Agro<span class="spectra">Espectra</span><i class="bi bi-activity"></i>
            </a> <span class="menu-icon" id="menuIcon"><i class="bi bi-list" id="icon-open"></i><i class="bi bi-x" id="icon-close"></i></span>
            <nav class="navigation">
                <ul>
                    <li id="inicio"><a href="{{ url('/') }}">Inicio</a></li>
                    <li><a href="{{ url('/buscadorFTIR') }}">Buscador</a></li>
                    <li><a href="{{ url('/comparar') }}">Comparar</a></li>
                    <li><a href="{{ url('/subir') }}">Subir</a></li>

                </ul>
            </nav>
        </div>
    </header>

    <section class="subir">
    <div class="container">

        <div class="mb-3">
            <p class="mb-1">
                Seleccione el origen del CSV (equipo/software) y cargue un espectro FTIR.
            </p>
            <small class="text-muted">
                Requisitos mínimos: archivo <b>.csv</b> o <b>.txt</b>, columnas <b>cm-1</b> y <b>%T</b>, y al menos <b>200 puntos</b>.
                <br>
                <b>Tipo 1 (PerkinElmer – Spectrum Two):</b> separador <code>;</code>. Usualmente la primera línea puede ser metadatos y el encabezado estar en la línea 2.
                <br>
                <b>Tipo 2 (Thermo Fisher – Nicolet OMNIC):</b> separador <code>,</code>. Estructura equivalente.
            </small>
        </div>

        {{-- Mensajes de sesión --}}
        @if (session('success'))
            <div class="alert alert-success">
                {!! nl2br(e(session('success'))) !!}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {!! nl2br(e(session('error'))) !!}
            </div>
        @endif

        <form id="uploadForm" action="{{ route('handle.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="fileType" class="form-label"><b>Tipo de archivo (equipo/software)</b></label>

                @if ($errors->has('fileType'))
                    <div class="alert alert-danger mt-2">
                        {{ $errors->first('fileType') }}
                    </div>
                @endif

                <select id="fileType" name="fileType" class="form-select" required>
                    <option value="">Selecciona un tipo...</option>
                    <option value="tipo1" {{ old('fileType') === 'tipo1' ? 'selected' : '' }}>
                        PerkinElmer – Spectrum Two (CSV con “;”)
                    </option>
                    <option value="tipo2" {{ old('fileType') === 'tipo2' ? 'selected' : '' }}>
                        Thermo Fisher – Nicolet (OMNIC) (CSV con “,”)
                    </option>

                    <option disabled>──────── Otros equipos ────────</option>

                    <option value="bruker" {{ old('fileType') === 'bruker' ? 'selected' : '' }}>
                        Bruker
                    </option>
                    <option value="shimadzu" {{ old('fileType') === 'shimadzu' ? 'selected' : '' }}>
                        Shimadzu
                    </option>
                    <option value="agilent" {{ old('fileType') === 'agilent' ? 'selected' : '' }}>
                        Agilent
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label for="fileInput" class="form-label"><b>Archivo CSV</b></label>

                @if ($errors->has('file'))
                    <div class="alert alert-danger mt-2">
                        {{ $errors->first('file') }}
                    </div>
                @endif

                <input type="file"
                       id="fileInput"
                       name="file"
                       accept=".csv,.txt,text/csv,text/plain"
                       class="form-control"
                       required>

                <small class="text-muted">
                    Solo se aceptan CSV/TXT exportados desde el equipo/software indicado. Si el separador no coincide con el tipo seleccionado, se rechazará con un mensaje explicativo.
                </small>
            </div>

            <div class="mb-3">
                @if ($errors->has('ceder_derechos'))
                    <div class="alert alert-danger mt-2">
                        {{ $errors->first('ceder_derechos') }}
                    </div>
                @endif

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="ceder_derechos" name="ceder_derechos" required>
                    <label class="form-check-label" for="ceder_derechos">
                        Confirmo que cuento con los derechos necesarios para subir este espectro y que, al subirlo, cedo a AgroEspectra el derecho de uso/almacenamiento del archivo para fines de análisis y construcción de base de datos.
                    </label>
                </div>

                <small class="text-muted">
                    Nota: los espectros generados por el usuario a partir de sus muestras son de su autoría/propiedad; al subirlos, usted autoriza su uso en la plataforma.
                    Las bibliotecas comerciales incluidas con ciertos equipos suelen estar protegidas/licenciadas e indexadas; no deben subirse si su licencia lo prohíbe.
                </small>
            </div>

            <button type="submit" class="btn btn-danger">
                Subir archivo
            </button>
        </form>

    </div>
</section>

    
    <!-- Pie de página -->
    <footer class="d-flex flex-column align-items-center justify-content-center">
        <div class="container text-center">
            <span class="text-muted small">© 2024 Agroespectra. Todos los derechos reservados.
                Para consultas o soporte técnico, contáctenos en support@ftirbd.com</span>
        </div>
    </footer>


    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- codigo js -->
    <!-- Agrega JavaScript para manejar el clic en el ícono del menú  -->
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
</body>

</html>