<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra - Comparar</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/buscador.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2/dist/css/select2.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>



<body class="bg-light">

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
    <h3 class="mb-3">Comparar espectros FTIR</h3>

    <div class="row g-3">
        <!-- Serie A -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Serie A (Biblioteca)</h5>

                    <label class="form-label">Archivo FTIR</label>
                    <select class="form-control select2" id="ftirA">
                        <option value="">Seleccione un archivo</option>
                        @foreach ($ftirFiles as $f)
                            <option value="{{ $f->ftir_id }}">{{ $f->nombre_ftir }}</option>
                        @endforeach
                    </select>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-outline-danger" id="downloadA" type="button" disabled>
                            Descargar CSV A
                        </button>
                        <small class="text-muted align-self-center" id="pointsA"></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Serie B -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Serie B</h5>

                    <div class="d-flex gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sourceB" id="bLib" value="library" checked>
                            <label class="form-check-label" for="bLib">Biblioteca</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sourceB" id="bUp" value="upload">
                            <label class="form-check-label" for="bUp">Subir CSV (temporal)</label>
                        </div>
                    </div>

                    <!-- B biblioteca -->
                    <div id="bLibraryBox">
                        <label class="form-label">Archivo FTIR</label>
                        <select class="form-control select2" id="ftirB">
                            <option value="">Seleccione un archivo</option>
                            @foreach ($ftirFiles as $f)
                                <option value="{{ $f->ftir_id }}">{{ $f->nombre_ftir }}</option>
                            @endforeach
                        </select>

                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-outline-danger" id="downloadB" type="button" disabled>
                                Descargar CSV B
                            </button>
                            <small class="text-muted align-self-center" id="pointsB"></small>
                        </div>
                    </div>

                    <!-- B upload -->
                    <div id="bUploadBox" style="display:none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Archivo CSV</label>
                                <input type="file" class="form-control" id="fileB" accept=".csv,text/csv">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Formato</label>
                                <select class="form-select" id="formatB">
                                    <option value="auto" selected>Auto-detectar</option>
                                    <option value="tipo1">Tipo 1 (;)</option>
                                    <option value="tipo2">Tipo 2 (,)</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 d-flex align-items-end">
                                <button class="btn btn-outline-secondary w-100" id="downloadUploadB" type="button" disabled>
                                    Descargar CSV B (temporal)
                                </button>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2" id="pointsUploadB"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="card mt-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-primary" id="btnCompare" type="button">
                Comparar
            </button>
            <div class="d-flex align-items-center gap-2">
    <label class="form-label mb-0 text-muted">Top</label>
    <input type="number" class="form-control" id="topMatches" value="5" min="1" max="50" style="width:90px;">
</div>
            <div id="msg" class="ms-2" style="display:none;"></div>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="card mt-3" id="chartCard" style="display:none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Resultado</h5>

    <div class="d-flex align-items-center gap-2">
        <small class="text-muted" id="chartInfo"></small>
        <button id="resetZoomCompareBtn" type="button" class="btn btn-sm btn-outline-secondary" style="display:none;">
            Reset zoom
        </button>
    </div>
</div>

            <div class="bg-white p-2 rounded">
                <canvas id="compareChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <div class="card mt-3" id="matchesCard" style="display:none;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Coincidencias en biblioteca</h5>
            <small class="text-muted" id="abInfo"></small>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <h6 class="mb-2">Serie A</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>FTIR ID</th>
                                <th>Nombre</th>
                                <th>Distancia</th>
                                <th>Similitud</th>
                                <th>Descargar</th>
                            </tr>
                        </thead>
                        <tbody id="matchesA"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <h6 class="mb-2">Serie B</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>FTIR ID</th>
                                <th>Nombre</th>
                                <th>Distancia</th>
                                <th>Similitud</th>
                                <th>Descargar</th>
                            </tr>
                        </thead>
                        <tbody id="matchesB2"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<footer class="d-flex flex-column align-items-center justify-content-center">
    <div class="container text-center">
        <span class="text-muted small">© 2024 Agroespectra. Todos los derechos reservados.</span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2/dist/js/select2.full.min.js"></script>

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

$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Seleccione un archivo FTIR",
        allowClear: true,
        minimumResultsForSearch: 0
    });
});
</script>

<script>
const msg = document.getElementById('msg');

function showMsg(type, text) {
    msg.style.display = 'block';
    msg.className = 'alert mb-0 py-2 px-3 alert-' + type;
    msg.textContent = text;
}

function hideMsg() {
    msg.style.display = 'none';
    msg.textContent = '';
}

function buildXYPoints(xArr, yArr) {
    const n = Math.min(xArr.length, yArr.length);
    const out = new Array(n);
    for (let i = 0; i < n; i++) {
        out[i] = { x: Number(xArr[i]), y: Number(yArr[i]) };
    }
    return out;
}

let chartInstance = null;
let lastUploadB = null; // guardamos serie upload para descarga temporal

const resetBtn = document.getElementById('resetZoomCompareBtn');

if (resetBtn) {
    resetBtn.addEventListener('click', () => {
        if (!chartInstance) return;
        chartInstance.resetZoom(); // requiere chartjs-plugin-zoom
    });
}


function renderChart(series1, series2) {
    const ctx = document.getElementById('compareChart').getContext('2d');

    const ds1 = {
        label: series1.name,
        data: buildXYPoints(series1.x, series1.y),
        borderWidth: 1,
        pointRadius: 0,
        tension: 0.1
    };

    const ds2 = {
        label: series2.name,
        data: buildXYPoints(series2.x, series2.y),
        borderWidth: 1,
        pointRadius: 0,
        tension: 0.1
    };

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx, {
    type: 'line',
    data: { datasets: [ds1, ds2] },
    options: {
        responsive: true,
        parsing: false,
        scales: {
            x: {
                type: 'linear',
                reverse: true, // FTIR típico (4000 -> 400)
                title: { display: true, text: 'cm⁻¹' }
            },
            y: {
                title: { display: true, text: 'Señal' },
                beginAtZero: false
            }
        },

        // ✅ ZOOM / PAN
        plugins: {
            zoom: {
                pan: {
                    enabled: true,
                    mode: 'x'
                },
                zoom: {
                    wheel: {
                        enabled: true
                    },
                    pinch: {
                        enabled: true
                    },
                    mode: 'x'
                }
            }
        }
    }
});

const rb = document.getElementById('resetZoomCompareBtn');
if (rb) rb.style.display = 'inline-block';

    document.getElementById('chartInfo').textContent =
        `A: ${series1.points} puntos | B: ${series2.points} puntos`;

    document.getElementById('chartCard').style.display = 'block';
    document.getElementById('chartCard').scrollIntoView({ behavior: 'smooth' });
}

function downloadCsvFromArrays(filename, x, y) {
    let csv = "Número de onda,Transmisión\n";
    const n = Math.min(x.length, y.length);
    for (let i=0;i<n;i++) csv += `${x[i]},${y[i]}\n`;

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/** Toggle B source UI */
function syncBSourceUI() {
    const source = document.querySelector('input[name="sourceB"]:checked').value;
    document.getElementById('bLibraryBox').style.display = (source === 'library') ? 'block' : 'none';
    document.getElementById('bUploadBox').style.display  = (source === 'upload') ? 'block' : 'none';
}

document.getElementById('bLib').addEventListener('change', syncBSourceUI);
document.getElementById('bUp').addEventListener('change', syncBSourceUI);
syncBSourceUI();

/** Descargas biblioteca */
document.getElementById('ftirA').addEventListener('change', () => {
    const id = document.getElementById('ftirA').value;
    document.getElementById('downloadA').disabled = !id;
    document.getElementById('pointsA').textContent = '';
});
document.getElementById('ftirB').addEventListener('change', () => {
    const id = document.getElementById('ftirB').value;
    document.getElementById('downloadB').disabled = !id;
    document.getElementById('pointsB').textContent = '';
});

document.getElementById('downloadA').addEventListener('click', () => {
    const id = document.getElementById('ftirA').value;
    if (!id) return;
    window.location.href = `/download-csv?ftir_id=${encodeURIComponent(id)}`;
});
document.getElementById('downloadB').addEventListener('click', () => {
    const id = document.getElementById('ftirB').value;
    if (!id) return;
    window.location.href = `/download-csv?ftir_id=${encodeURIComponent(id)}`;
});

/** Descarga upload B (temporal) */
document.getElementById('downloadUploadB').addEventListener('click', () => {
    if (!lastUploadB) return;
    const name = (lastUploadB.name || 'temporal').replace('.csv','');
    downloadCsvFromArrays(`${name}_temporal.csv`, lastUploadB.x, lastUploadB.y);
});


const downloadBase = "{{ route('download.csv') }}";

function renderMatchTable(tbodyId, results) {
    const tbody = document.getElementById(tbodyId);
    tbody.innerHTML = '';

    if (!results || results.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-muted">Sin resultados</td></tr>`;
        return;
    }

    results.forEach((r, i) => {
        const url = `${downloadBase}?ftir_id=${encodeURIComponent(r.ftir_id)}`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i + 1}</td>
            <td>${r.ftir_id}</td>
            <td>${r.nombre_ftir ?? ''}</td>
            <td>${Number(r.distancia).toFixed(6)}</td>
            <td>${Number(r.similitud_pct).toFixed(2)}%</td>
            <td>
                <a class="btn btn-sm btn-outline-danger" href="${url}">
                    Descargar CSV
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/** Comparar */
document.getElementById('btnCompare').addEventListener('click', async () => {
    hideMsg();

    const ftirA = document.getElementById('ftirA').value;
    if (!ftirA) {
        showMsg('warning', 'Seleccione la Serie A (biblioteca).');
        return;
    }

    const sourceB = document.querySelector('input[name="sourceB"]:checked').value;

    const fd = new FormData();
    fd.append('ftir_id1', ftirA);
    fd.append('source2', sourceB);

    if (sourceB === 'library') {
        const ftirB = document.getElementById('ftirB').value;
        if (!ftirB) {
            showMsg('warning', 'Seleccione la Serie B (biblioteca) o cambie a “Subir CSV”.');
            return;
        }
        fd.append('ftir_id2', ftirB);
    } else {
        const file = document.getElementById('fileB').files?.[0];
        if (!file) {
            showMsg('warning', 'Seleccione el CSV temporal para la Serie B.');
            return;
        }
        fd.append('file', file);
        fd.append('format', document.getElementById('formatB').value);
    }

    try {
        showMsg('info', 'Procesando comparación...');

const top = document.getElementById('topMatches')?.value || 5;
fd.append('top', top);

        const res = await fetch('/api/ftir/compare', {
            method: 'POST',
            body: fd
        });

        const data = await res.json();

        if (!res.ok) {
            showMsg('danger', data.error || data.message || 'Error al comparar.');
            return;
        }

        const s1 = data.series1;
        const s2 = data.series2;

        // UI puntos
        document.getElementById('pointsA').textContent = `${s1.points} puntos`;
        if (s2.source === 'library') {
            document.getElementById('pointsB').textContent = `${s2.points} puntos`;
            document.getElementById('pointsUploadB').textContent = '';
            lastUploadB = null;
            document.getElementById('downloadUploadB').disabled = true;
        } else {
            document.getElementById('pointsUploadB').textContent = `${s2.points} puntos | delimitador: ${s2.delimiter}`;
            lastUploadB = s2;
            document.getElementById('downloadUploadB').disabled = false;
            document.getElementById('pointsB').textContent = '';
        }

        renderChart(s1, s2);
        // info AB
if (data.ab) {
    document.getElementById('abInfo').textContent =
        `A↔B distancia: ${Number(data.ab.distancia).toFixed(6)} | similitud: ${Number(data.ab.similitud_pct).toFixed(2)}%`;
}

// tablas de coincidencias
if (data.matches) {
    renderMatchTable('matchesA',  data.matches.series1 || []);
    renderMatchTable('matchesB2', data.matches.series2 || []);
    document.getElementById('matchesCard').style.display = 'block';
}
        showMsg('success', 'Comparación generada correctamente.');

    } catch (e) {
        showMsg('danger', 'Error de red o servidor.');
    }
});
</script>

</body>
</html>
