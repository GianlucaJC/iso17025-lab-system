<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Cronologia Modifiche - {{ $modelDisplayName }} #{{ $record->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <div>
                <a href="{{ route('acceptance.create') }}" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Nuova Accettazione
                </a>
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
            </div>
            <div class="d-flex align-items-center">
                @if(Session::has('user'))
                    @php
                        $user = Session::get('user');
                        $roleId = $user['user17025'] ?? null;
                        $roleMap = [1 => 'Amministratore', 2 => 'Resp. Accettazione', 3 => 'Tecnico di Laboratorio', 4 => 'Resp. Qualità'];
                        $badgeColorMap = [1 => 'bg-danger', 2 => 'bg-info text-dark', 3 => 'bg-primary', 4 => 'bg-success'];
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-history me-2"></i>Cronologia Modifiche per {{ $modelDisplayName }} #{{ $record->id }}</h3>
            <a href="{{ route('acceptance.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Torna all'elenco</a>
        </div>

        @forelse($logs as $log)
            <div class="card mb-4">
                <div class="card-header bg-primary-subtle">
                    <strong class="text-primary-emphasis">{{ ucfirst($log->event) }}</strong> da <strong>{{ $log->user_name }}</strong>
                    <span class="float-end text-muted">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                <div class="card-body">
                    @if($log->event == 'updated' && $log->modification_reason)
                        <div class="alert alert-warning p-2">
                            <strong>Motivazione:</strong> {{ $log->modification_reason }}
                        </div>
                    @endif

                    @php
                        $old_values = $log->old_values ?? [];
                        $new_values = $log->new_values ?? [];
                        $all_keys = array_unique(array_merge(array_keys($old_values), array_keys($new_values)));
                        $ignored_keys = ['id', 'created_at', 'updated_at', 'user_id', 'operator_id', 'acceptance_id'];
                        $keys_to_show = array_diff($all_keys, $ignored_keys);
                    @endphp

                    @if(!empty($keys_to_show))
                        <table class="table table-bordered table-sm" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">Campo</th>
                                    <th style="width: 40%;">Valore Precedente</th>
                                    <th style="width: 40%;">Nuovo Valore</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($keys_to_show as $key)
                                    @php
                                        $old = $old_values[$key] ?? null;
                                        $new = $new_values[$key] ?? null;
                                        if (is_array($old)) $old = json_encode($old, JSON_PRETTY_PRINT);
                                        if (is_array($new)) $new = json_encode($new, JSON_PRETTY_PRINT);
                                        $is_changed = $old != $new;
                                    @endphp
                                    <tr class="{{ $is_changed ? 'table-warning' : '' }}">
                                        <td><strong>{{ $key }}</strong></td>
                                        <td><pre class="mb-0"><code>{{ $old }}</code></pre></td>
                                        <td><pre class="mb-0"><code>{{ $new }}</code></pre></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                         <p class="text-muted">Nessuna modifica ai dati registrata per questo evento.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-info">Nessuna cronologia di modifiche trovata per questo record.</div>
        @endforelse
    </main>

    <footer class="mt-auto text-center py-3 bg-white">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software - versione 1.0</small>
    </footer>
</body>
</html>
