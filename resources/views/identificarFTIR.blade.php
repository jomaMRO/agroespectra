<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra - Identificar FTIR</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/buscador.css') }}">

</head>
<body class="bg-light">

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
@includeIf('partials.header') {{-- si no existe, quite esta línea y pegue su header --}}

<div class="container py-4">
    <h3 class="mb-3">Identificar espectro FTIR</h3>

    <div class="card mb-3">
        <div class="card-body">
            <form id="identifyForm">
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
                        <label class="form-label">Top</label>
                        <input type="number" class="form-control" id="top" value="5" min="1" max="50">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Comparar</button>
                    </div>
                </div>
            </form>

            <div class="mt-3" id="msg" style="display:none;"></div>
        </div>
    </div>

    <div class="card" id="resultsCard" style="display:none;">
        <div class="card-body">
            <h5 class="mb-3">Resultados</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>FTIR ID</th>
                            <th>Nombre</th>
                            <th>Distancia</th>
                            <th>porcentaje de similitud aproximado</th>
                            <th>Descargar</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody"></tbody>
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
const downloadBase = "{{ route('download.csv') }}"; 


document.getElementById('identifyForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById('ftirFile');
    const format = document.getElementById('format').value;
    const top = document.getElementById('top').value;

    const msg = document.getElementById('msg');
    const resultsCard = document.getElementById('resultsCard');
    const resultsBody = document.getElementById('resultsBody');

    msg.style.display = 'block';
    msg.className = 'alert alert-info';
    msg.textContent = 'Procesando...';

    resultsCard.style.display = 'none';
    resultsBody.innerHTML = '';

    if (!fileInput.files || !fileInput.files[0]) {
        msg.className = 'alert alert-danger';
        msg.textContent = 'Seleccione un archivo.';
        return;
    }

    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    fd.append('format', format);
    fd.append('top', top);

    try {
        const res = await fetch('/api/ftir/match-upload', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: fd
        });

        const data = await res.json();

        if (!res.ok) {
            msg.className = 'alert alert-danger';
            msg.textContent = data.error || data.message || 'Error al comparar.';
            return;
        }

        msg.className = 'alert alert-success';
        msg.textContent = `Archivo: ${data.query_filename}. Coincidencias: ${data.results.length}`;

        data.results.forEach((r, i) => {
            const tr = document.createElement('tr');

            const url = `${downloadBase}?ftir_id=${encodeURIComponent(r.ftir_id)}`;

            tr.innerHTML = `
                <td>${i+1}</td>
                <td>${r.ftir_id}</td>
                <td>${r.nombre_ftir ?? ''}</td>
                <td>${Number(r.distancia).toFixed(6)}</td>
                <td>${r.similitud_pct.toFixed(2)}%</td>
                <td>
                    <a class="btn btn-sm btn-outline-danger" href="${url}">
                        Descargar CSV
                    </a>
                </td>
            `;
            resultsBody.appendChild(tr);
        });

        resultsCard.style.display = 'block';
    } catch (err) {
        msg.className = 'alert alert-danger';
        msg.textContent = 'Error de red o servidor.';
    }
});
</script>
</body>
</html>
