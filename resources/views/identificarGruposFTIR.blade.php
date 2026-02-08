<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra - Grupos funcionales FTIR</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/buscador.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1"></script>
</head>
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
                <li><a href="{{ route('identificar.grupos') }}">Grupos</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container py-4">
    <h3 class="mb-3">Detectar picos y sugerir grupos funcionales</h3>

    <div class="card mb-3">
        <div class="card-body">
            <form id="peaksForm">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Archivo CSV</label>
                        <input type="file" class="form-control" id="ftirFile" accept=".csv,text/csv" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" id="format">
                            <option value="auto" selected>Auto-detectar</option>
                            <option value="tipo1">Tipo 1</option>
                            <option value="tipo2">Tipo 2</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Modo Y</label>
                        <select class="form-select" id="mode">
                            <option value="auto" selected>Auto</option>
                            <option value="transmittance">%T (picos = mínimos)</option>
                            <option value="absorbance">Abs (picos = máximos)</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Analizar</button>
                    </div>
                </div>
            </form>

            <div class="mt-3" id="msg" style="display:none;"></div>
        </div>
    </div>

    <!--  Card Resultado / Gráfico -->
<div class="card mt-3" id="resultadosGrafico" style="display:none;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Espectro cargado</h5>
      <small class="text-muted" id="chartInfo"></small>
    </div>

    <div class="bg-white p-2 rounded">
      <canvas id="myChart" height="110"></canvas>
    </div>

    <div class="d-flex justify-content-center gap-2 mt-2">
      <button id="resetZoomBtn" class="btn btn-outline-secondary" type="button">
        Reset zoom
      </button>
    </div>
  </div>
</div>
    <div class="card" id="summaryCard" style="display:none;">
        <div class="card-body">
            <h5 class="mb-3">Resumen de lectura</h5>
            <div id="summaryBody"></div>
        </div>
    </div>

    {{-- NUEVO: card de picos --}}
    <div class="card mt-3" id="peaksCard" style="display:none;">
        <div class="card-body">
            <h5 class="mb-3">Picos detectados</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>cm⁻¹</th>
                            <th>Y</th>
                            <th>Prominencia (rel.)</th>
                        </tr>
                    </thead>
                    <tbody id="peaksBody"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card mt-3" id="groupsCard" style="display:none;">
  <div class="card-body">
    <h5 class="mb-3">Grupos sugeridos por pico</h5>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>cm⁻¹</th>
            <th>Sugerencias (top)</th>
          </tr>
        </thead>
        <tbody id="groupsBody"></tbody>
      </table>
    </div>
  </div>
</div>

<div class="card mt-3" id="groupsSummaryCard" style="display:none;">
  <div class="card-body">
    <h5 class="mb-3">Resumen global (lo más probable)</h5>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead>
          <tr>
            <th>Grupo</th>
            <th>Enlace</th>
            <th>Soporte (picos)</th>
            <th>Mejor score</th>
          </tr>
        </thead>
        <tbody id="groupsSummaryBody"></tbody>
      </table>
    </div>
  </div>
</div>

</div>

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

<script>
document.getElementById('peaksForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById('ftirFile');
    const format = document.getElementById('format').value;
    const mode = document.getElementById('mode').value;

    const msg = document.getElementById('msg');
    const summaryCard = document.getElementById('summaryCard');
    const summaryBody = document.getElementById('summaryBody');

    // NUEVO: elementos de picos
    const peaksCard = document.getElementById('peaksCard');
    const peaksBody = document.getElementById('peaksBody');

    const groupsSummaryCard = document.getElementById('groupsSummaryCard');
const groupsSummaryBody = document.getElementById('groupsSummaryBody');

const resultadosGrafico = document.getElementById('resultadosGrafico');
const chartInfo = document.getElementById('chartInfo');


    msg.style.display = 'block';
    msg.className = 'alert alert-info';
    msg.textContent = 'Procesando...';

    summaryCard.style.display = 'none';
    summaryBody.innerHTML = '';

    // NUEVO: reset de picos
    peaksCard.style.display = 'none';
    peaksBody.innerHTML = '';
    groupsSummaryCard.style.display = 'none';
groupsSummaryBody.innerHTML = '';

// NUEVO: reset del gráfico
resultadosGrafico.style.display = 'none';
chartInfo.textContent = '';
if (typeof myChart !== 'undefined' && myChart) {
  myChart.destroy();
  myChart = null;
}


    if (!fileInput.files || !fileInput.files[0]) {
        msg.className = 'alert alert-danger';
        msg.textContent = 'Seleccione un archivo.';
        return;
    }

    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    fd.append('format', format);
    fd.append('mode', mode);

    try {
        const res = await fetch('/api/ftir/peaks-upload', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: fd
        });

        const data = await res.json();

        if (!res.ok) {
            msg.className = 'alert alert-danger';
            msg.textContent = data.error || data.message || 'Error al analizar.';
            return;
        }

        msg.className = 'alert alert-success';
        msg.textContent = `Archivo: ${data.query_filename}. Puntos: ${data.points_read}`;

        summaryBody.innerHTML = `
            <div><strong>Formato:</strong> ${data.format}</div>
            <div><strong>Delimitador:</strong> ${data.delimiter}</div>
            <div><strong>Modo:</strong> ${data.mode} ${data.mode_resolved ? '(' + data.mode_resolved + ')' : ''}</div>
            <div><strong>Puntos leídos:</strong> ${data.points_read}</div>
            <div><strong>X primero / último:</strong> ${data.x_first} / ${data.x_last}</div>
            <div><strong>Y min / max:</strong> ${data.y_min} / ${data.y_max}</div>
        `;

        summaryCard.style.display = 'block';

        // NUEVO: dibujar gráfico con los arrays del backend
if (Array.isArray(data.nro_onda) && Array.isArray(data.transmision) && data.nro_onda.length > 0) {
  updateChart(data.nro_onda, data.transmision, data.query_filename);

  const pts = Math.min(data.nro_onda.length, data.transmision.length);
  chartInfo.textContent = `${pts} puntos`;

  resultadosGrafico.style.display = 'block';
}


        // NUEVO: render de picos
       // Render de grupos por pico
if (Array.isArray(data.groups_peaks) && data.groups_peaks.length > 0) {
  data.groups_peaks.forEach((p, i) => {    

    const groups = Array.isArray(p.groups) ? p.groups : [];

    const pretty = groups.length
      ? groups.map(g => `${g.group_name} (${g.range})`).join('<br>')
      : '<span class="text-muted">Sin coincidencias</span>';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${i+1}</td>
      <td>${Number(p.wn).toFixed(2)}</td>
      <td>${pretty}</td>
    `;
    groupsBody.appendChild(tr);
  });

  groupsCard.style.display = 'block';
}

// Render resumen global
if (Array.isArray(data.group_summary) && data.group_summary.length > 0) {
  data.group_summary.forEach(g => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${g.group_name ?? ''}</td>
      <td>${g.bond ?? ''}</td>
      <td>${g.count ?? 0}</td>
      <td>${Number(g.best_score ?? 0).toFixed(3)}</td>
    `;
    groupsSummaryBody.appendChild(tr);
  });
  groupsSummaryCard.style.display = 'block';
}

    } catch (err) {
        msg.className = 'alert alert-danger';
        msg.textContent = 'Error de red o servidor.';
    }
});
</script>

<script>
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
        data: buildXYPoints(nro_onda, transmision),
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
        parsing: false,
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
    myChart.resetZoom();
  });
</script>

</body>
</html>
