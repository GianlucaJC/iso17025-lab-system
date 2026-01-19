<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Elenco Accettazioni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    {{-- Dipendenze per DataTables --}}
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
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
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul me-2"></i>Elenco Accettazioni Campioni</h3>
            </div>
            <div class="card-body">
                <table id="acceptancesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>N. Accettazione</th>
                            <th>Lotto</th>
                            <th>Data Accettazione</th>
                            <th>Test Eseguiti</th>
                            <th>Operatore</th>
                            <th>Data Creazione</th>
                            <th>Esecuzione Test</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $testNameMap = [
                                'test1' => 'Test A (Controllo del pH)',
                                'test2' => 'Test B (Produttività, Metodo Qualitativo)',
                                'test3' => 'Test C (Controllo della contaminazione microbica)'
                            ];
                            $currentUser = Session::get('user');
                        @endphp
                        @foreach ($acceptances as $acceptance)
                            <tr>
                                <td>{{ $acceptance->acceptance_number }}</td>
                                <td>{{ $acceptance->lotto }}</td>
                                <td>{{ \Carbon\Carbon::parse($acceptance->acceptance_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if(!empty($acceptance->tests))
                                        @foreach($acceptance->tests as $testKey)
                                            <div class="mb-1">
                                                <span class="badge bg-secondary">{{ $testNameMap[$testKey] ?? $testKey }}</span>
                                                @if(is_array($acceptance->double_tests) && in_array($testKey, $acceptance->double_tests))
                                                    <span class="badge bg-info text-dark" title="Eseguito in doppio">2x</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                {{-- Usiamo la mappa degli utenti passata dal controller per trovare il nome --}}
                                <td>{{ $usersMap[$acceptance->user_id]['operatore'] ?? 'N/D' }}</td>
                                <td>{{ $acceptance->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if(in_array('test1', $acceptance->tests))
                                        {{-- Controlla se il risultato del test esiste tramite la relazione --}}
                                        @if($acceptance->testAResult)
                                            @php
                                                $isOwner = $acceptance->testAResult->operator_id == $currentUser['id'];
                                            @endphp
                                            @if($acceptance->testAResult->validator_id)
                                                {{-- Test completato e validato (sola lettura per tutti) --}}
                                                <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-success mb-1" title="Visualizza Test A (Validato)">
                                                    <i class="fas fa-user-check"></i> Test A
                                                </a>
                                            @else
                                                @if($isOwner)
                                                    {{-- Test completato, non validato, modificabile dal proprietario --}}
                                                    <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-warning mb-1" title="Modifica Risultati Test A">
                                                        <i class="fas fa-edit"></i> Test A
                                                    </a>
                                                @else
                                                    {{-- Test completato, non validato, in sola lettura per gli altri --}}
                                                    <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test A">
                                                        <i class="fas fa-eye"></i> Test A
                                                    </a>
                                                @endif
                                            @endif
                                        @else
                                            <a href="{{ route('test-a.create', $acceptance) }}" class="btn btn-sm btn-outline-primary mb-1" title="Esegui Test A: Controllo pH">
                                                <i class="fas fa-vial"></i> Test A
                                            </a>
                                        @endif
                                        {{-- Pulsante Cronologia per Test A --}}
                                        @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                                            @if($acceptance->testAResult)
                                                <a href="{{ route('history.show', ['modelNameShort' => 'test-a-result', 'id' => $acceptance->testAResult->id]) }}" class="btn btn-sm btn-outline-secondary mb-1" title="Cronologia Modifiche Test A">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                            @endif
                                        @endif
                                    @endif
                                    {{-- Qui verranno aggiunti i link per gli altri test --}}
                                    {{-- @if(in_array('test2', $acceptance->tests)) <a href... >Test B</a> @endif --}}
                                    {{-- @if(in_array('test3', $acceptance->tests)) <a href... >Test C</a> @endif --}}
                                </td>
                                <td class="text-nowrap">
                                    @if($currentUser && ($acceptance->user_id == $currentUser['id']))
                                        <a href="{{ route('acceptance.edit', $acceptance) }}" class="btn btn-primary btn-sm" title="Modifica">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('acceptance.edit', $acceptance) }}" class="btn btn-info btn-sm" title="Visualizza">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    {{-- Pulsante Cronologia per Accettazione (solo admin) --}}
                                    @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                                        <a href="{{ route('history.show', ['modelNameShort' => 'acceptance', 'id' => $acceptance->id]) }}" class="btn btn-secondary btn-sm" title="Cronologia Modifiche Accettazione">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 bg-light">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>

    {{-- Script per DataTables --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#acceptancesTable').DataTable({
                language: {
                    "sEmptyTable":     "Nessun dato presente nella tabella",
                    "sInfo":           "Vista da _START_ a _END_ di _TOTAL_ elementi",
                    "sInfoEmpty":      "Vista da 0 a 0 di 0 elementi",
                    "sInfoFiltered":   "(filtrati da _MAX_ elementi totali)",
                    "sInfoPostFix":    "",
                    "sInfoThousands":  ".",
                    "sLengthMenu":     "Visualizza _MENU_ elementi",
                    "sLoadingRecords": "Caricamento...",
                    "sProcessing":     "Elaborazione...",
                    "sSearch":         "Cerca:",
                    "sZeroRecords":    "La ricerca non ha portato alcun risultato.",
                    "oPaginate": {
                        "sFirst":      "Inizio",
                        "sPrevious":   "Precedente",
                        "sNext":       "Successivo",
                        "sLast":       "Fine"
                    },
                    "oAria": {
                        "sSortAscending":  ": attiva per ordinare la colonna in ordine crescente",
                        "sSortDescending": ": attiva per ordinare la colonna in ordine decrescente"
                    }
                }
            });
        });
    </script>
</body>
</html>