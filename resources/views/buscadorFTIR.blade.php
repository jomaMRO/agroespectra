<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra - Buscador</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Estilos del proyecto -->
    <link rel="stylesheet" href="{{ asset('css/buscador.css') }}">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2/dist/css/select2.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1"></script>

</head>

<body class="bg-light">

<!-- Header (igual al segundo) -->
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
                <li id="inicio"><a href="{{ url('/') }}">Inicio</a></li>
                <li><a href="{{ url('/buscadorFTIR') }}">Buscador</a></li>
                <li><a href="{{ url('/comparar') }}">Comparar</a></li>
                <li><a href="{{ url('/subir') }}">Subir</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container py-4">
   <h3 class="mb-3 d-flex align-items-center gap-2">
    biblioteca de espectros FTIR

    <!-- Disclaimer discreto -->
    <a
        href="#"
        class="text-decoration-none"
        tabindex="0"
        role="button"
        aria-label="Aviso sobre calidad y responsabilidad de los espectros"
        data-bs-toggle="popover"
        data-bs-trigger="focus"
        data-bs-placement="right"
        data-bs-title="Aviso"
data-bs-content="Los espectros son aportes de la comunidad. AgroEspectra actúa como repositorio y no asume responsabilidad por su exactitud, integridad o condiciones de adquisición. El uso e interpretación de la información corresponde al usuario; se recomienda contrastar con sus propios controles."
    >
        <i class="bi bi-exclamation-circle-fill text-warning"></i>
    </a>
</h3>


    <div class="row g-3">
        <!-- Col principal -->
        <div class="col-12 col-lg-8">

            <!-- Card Buscador -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Consultar biblioteca</h5>
                    <p class="text-muted mb-3">
                        Seleccione un archivo FTIR de la biblioteca y presione <b>Buscar</b> para visualizar el gráfico y habilitar la descarga en CSV.
                        En el lateral se muestran archivos recientes y populares.
                    </p>

                    <form class="d-flex gap-2" role="search" id="searchForm" action="{{ route('ftir.search') }}" method="POST">
                        @csrf
                        <select class="form-control select2" name="nombre" id="searchInput">
                            <option value="">Ingrese o seleccione un archivo FTIR</option>
                            @foreach ($ftirFiles as $nombre)
                                <option value="{{ $nombre }}">{{ $nombre }}</option>
                            @endforeach
                        </select>

                        <button class="btn btn-primary" type="submit">
                            Buscar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Card Resultado / Gráfico (estilo del segundo) -->
            <div class="card mt-3" id="resultadosGrafico" style="display:none;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Resultado</h5>
                        <small class="text-muted" id="chartInfo"></small>
                    </div>

                    <div class="bg-white p-2 rounded">
                        <canvas id="myChart" height="110"></canvas>
                    </div>
                <div class="d-flex justify-content-center gap-2">
                    <button id="downloadCSVButton" class="btn btn-danger">Descargar CSV</button>
                    <button id="resetZoomBtn" class="btn btn-outline-secondary" type="button">
                        Reset zoom
                    </button>
                </div>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Archivos recientes</h5>

                    <!-- Mantiene su “ticker” original -->
                    <div class="ticker">
                        <ul class="mb-0">
                            @foreach ($recentFiles as $file)
                                <li>{{ $file->nombre_ftir }}</li>
                            @endforeach
                            @foreach ($recentFiles as $file)
                                <li>{{ $file->nombre_ftir }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Archivos populares</h5>

                    <div class="ticker">
                        <ul class="mb-0">
                            @foreach ($popularFiles as $file)
                                <li>{{ $file->nombre_ftir }}</li>
                            @endforeach
                            @foreach ($popularFiles as $file)
                                <li>{{ $file->nombre_ftir }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<footer class="d-flex flex-column align-items-center justify-content-center">
    <div class="container text-center">
        <span class="text-muted small">
            © 2024 Agroespectra. Todos los derechos reservados. Para consultas o soporte técnico, contáctenos en support@ftirbd.com
        </span>
    </div>
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2/dist/js/select2.full.min.js"></script>

<!-- Menú (misma lógica) -->
 <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el);
        });
    });
</script>

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

<!-- Descarga CSV (misma lógica, por nombre) -->
<script>
    document.getElementById('downloadCSVButton').addEventListener('click', function() {
        const nombre = document.getElementById('searchInput').value;
        window.location.href = `/download-csv?nombre=${encodeURIComponent(nombre)}`;
    });
</script>

<!-- Select2 (misma lógica) -->
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Seleccione un archivo FTIR",
            allowClear: true,
            minimumResultsForSearch: 0
        });
    });
</script>

<!-- Fetch + Chart (misma lógica; chart con estilo del segundo) -->
<script>
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const nombre = document.getElementById('searchInput').value;
        if (!nombre) {
            alert('No válido, ingrese un dato.');
            return;
        }

        fetch('/api/search/ftir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ nombre: nombre })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    document.getElementById('downloadCSVButton').style.display = 'none';
                    document.getElementById('resultadosGrafico').style.display = 'none';
                    document.getElementById('chartInfo').textContent = '';
                } else {
                    updateChart(data.nro_onda, data.transmision, data.nombre_ftir);

                    // Mostrar card resultado + botón
                    document.getElementById('resultadosGrafico').style.display = 'block';
                    document.getElementById('downloadCSVButton').style.display = 'inline-block';

                    // Info similar al segundo
                    const pts = Math.min(data.nro_onda.length, data.transmision.length);
                    document.getElementById('chartInfo').textContent = `${pts} puntos`;

                    document.getElementById('resultadosGrafico').scrollIntoView({ behavior: 'smooth' });
                }
            });
    });

    var myChart;

    function buildXYPoints(xArr, yArr) {
        const n = Math.min(xArr.length, yArr.length);
        const out = new Array(n);
        for (let i = 0; i < n; i++) out[i] = { x: Number(xArr[i]), y: Number(yArr[i]) };
        return out;
    }

   function updateChart(nro_onda, transmision, nombre_ftir) {
    const ctx = document.getElementById('myChart').getContext('2d');

    if (myChart) myChart.destroy();

    const chartData = {
        datasets: [{
            label: nombre_ftir,
            data: buildXYPoints(nro_onda, transmision), // x,y reales
            borderWidth: 1,
            pointRadius: 0,
            tension: 0.1
        }]
    };

    myChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            parsing: false, // IMPORTANTE cuando se usa {x,y}
            scales: {
                x: {
                    type: 'linear',
                    reverse: true,
                    title: { display: true, text: 'cm⁻¹' }
                },
                y: {
                    beginAtZero: false,
                    title: { display: true, text: 'Señal' }
                }
            },
            plugins: {
                zoom: {
                    pan: { enabled: true, mode: 'x' },
                    zoom: {
                        wheel: { enabled: true },
                        pinch: { enabled: true },
                        mode: 'x'
                    }
                }
            }
        }
    });
}

document.getElementById('resetZoomBtn').addEventListener('click', () => {
    if (!myChart) return;
    myChart.resetZoom();   // requiere chartjs-plugin-zoom
});
</script>

</body>
</html>
