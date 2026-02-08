<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroEspectra | Panel</title>

    <!-- bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- letras -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- su CSS -->
    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">

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

        .panel-bg{
            min-height: 100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding: 3rem 1rem;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(1200px 500px at 10% 10%, rgba(123,44,191,.18), transparent 60%),
                radial-gradient(900px 500px at 90% 20%, rgba(43,42,143,.18), transparent 60%),
                linear-gradient(180deg, #ffffff, #f6f7fb);
        }

        .panel-bg::before{
            content:"";
            position:absolute;
            inset:-120px -120px auto auto;
            width:420px;height:420px;
            border-radius:999px;
            background: rgba(123,44,191,.12);
        }
        .panel-bg::after{
            content:"";
            position:absolute;
            inset:auto auto -140px -140px;
            width:520px;height:520px;
            border-radius:999px;
            background: rgba(43,42,143,.12);
        }

        .panel-card{
            width: 100%;
            max-width: 720px;
            border: 1px solid rgba(0,0,0,.06);
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0,0,0,.08);
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(6px);
            position: relative;
            z-index: 2;
        }


        .pill{
            display:inline-flex;
            gap:.5rem;
            align-items:center;
            padding:.35rem .75rem;
            border-radius:999px;
            border:1px solid rgba(0,0,0,.08);
            background:#fff;
            font-size:.85rem;
        }

        .kv{
            border: 1px solid rgba(0,0,0,.06);
            border-radius: 14px;
            padding: .85rem 1rem;
            background: #fff;
            height: 100%;
        }
        .kv .k{ color: rgba(0,0,0,.6); font-size:.85rem; }
        .kv .v{ font-weight:700; word-break: break-word; }

        .btn-brand{
            border:0;
            border-radius: 14px;
            padding: .7rem 1rem;
            background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
        }
        .btn-brand:hover{ opacity:.95; }

        .btn-soft{
            border-radius: 14px;
            padding: .7rem 1rem;
        }

        .mini-muted{
            color: rgba(0,0,0,.65);
            font-size: .92rem;
            line-height: 1.35rem;
        }
    </style>
    <style class="darkreader">
  .brand-gradient{
    background: linear-gradient(90deg, var(--brand-1), var(--brand-2));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-weight: 700;
  }
</style>

</head>

<body>
@php($u = auth()->user())

<div class="panel-bg">
    <div class="panel-card p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span class="pill"><i class="bi bi-person-check"></i> Sesión iniciada</span>
            <span class="pill">
                <i class="bi bi-shield-check"></i>
                {{ $u->email_verified_at ? 'Email verificado' : 'Email no verificado' }}
            </span>
        </div>

        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="d-flex align-items-center justify-content-center"
                 style="width:52px;height:52px;border-radius:16px;background:rgba(123,44,191,.10);border:1px solid rgba(123,44,191,.18);">
                <i class="bi bi-activity fs-4"></i>
            </div>
            <div>
                <h1 class="mb-0" style="font-weight:700;">
                    Panel <span
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
                </h1>
                <div class="mini-muted">Información básica de su cuenta.</div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-12">
                <div class="kv">
                    <div class="k">Usuario</div>
                    <div class="v">{{ $u->name ?? '—' }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="kv">
                    <div class="k">Email</div>
                    <div class="v">{{ $u->email }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="kv">
                    <div class="k">Verificación</div>
                    <div class="v">{{ $u->email_verified_at ? 'Verificado' : 'No verificado' }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ url('/') }}" class="btn btn-brand text-white">
                <i class="bi bi-house-door me-2"></i> Ir a Home
            </a>

            <a href="{{ route('profile.edit') }}" class="btn btn-outline-dark btn-soft">
                <i class="bi bi-pencil-square me-2"></i> Editar perfil
            </a>

            <form method="POST" action="{{ route('logout') }}" class="ms-auto m-0">
                @csrf
                <button type="submit" class="btn btn-primary btn-soft">
                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
