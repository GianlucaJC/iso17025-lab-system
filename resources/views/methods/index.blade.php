<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Gestione revisioni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div>
                @if(!($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4))
                    <a href="{{ route('acceptance.create') }}" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i>Nuova Accettazione
                    </a>
                @endif
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
                @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('instruments.index') }}">Gestione Strumenti</a>
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('methods.index') }}">Gestione revisioni</a>
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('user-management.redirect') }}" target="_blank">
                        <i class="fas fa-users-cog me-1"></i>Gestione Utenti
                    </a>
                @endif
            </div>
            <div class="d-flex align-items-center">
                @if(Session::has('user'))
                    @php
                        $user = Session::get('user');
                        $roleId = $user['user17025'] ?? null;
                        $roleMap = [1 => 'Admin', 3 => 'Tecnico di Laboratorio', 4 => 'Responsabile Laboratorio'];
                        $badgeColorMap = [1 => 'bg-danger', 3 => 'bg-primary', 4 => 'bg-success'];
                        $roleName = $roleMap[$roleId] ?? 'N/D';
                        $badgeColor = $badgeColorMap[$roleId] ?? 'bg-secondary';
                    @endphp
                    <span class="navbar-text me-3">
                        <i class="fas fa-user me-1"></i>
                        {{ $user['operatore'] ?? $user['username'] }}
                        <span class="badge rounded-pill {{ $badgeColor }} ms-1">{{ $roleName }}</span>
                    </span>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mt-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book me-2"></i>Gestione revisioni</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">In questa sezione è possibile aggiornare la stringa di revisione per ogni metodo di prova. Questa informazione verrà utilizzata nella generazione dei Rapporti di Prova (PDF).</p>
                <form method="POST" action="{{ route('methods.update') }}">
                    @csrf
                    @method('PUT')

                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Metodo</th>
                                <th>Stringa di Revisione</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($methods as $key => $method)
                            <tr>
                                <td>
                                    <label for="revision_{{ $key }}" class="form-label fw-bold mb-0">{{ $method->method_name }}</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" id="revision_{{ $key }}" name="revisions[{{ $key }}][revision_string]" value="{{ old('revisions.'.$key.'.revision_string', $method->revision_string) }}" required>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salva Modifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 bg-light">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software - versione 1.0</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
