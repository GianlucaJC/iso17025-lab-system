<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Elenco Accettazioni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom width for the main content area */
        main.container {
            max-width: 1340px; /* Default Bootstrap container-xl is 1140px, adding 200px */
        }
        /* Adjust for larger screens (xxl breakpoint and above) */
        @media (min-width: 1400px) {
            main.container {
                max-width: 1520px; /* Default Bootstrap container-xxl is 1320px, adding 200px */
            }
        }
    </style>
    {{-- Dipendenze per DataTables --}}
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    {{-- Dipendenza per SweetAlert2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                        $roleMap = [1 => 'Admin', 3 => 'Tecnico di Laboratorio', 4 => 'Responsabile Laboratorio']; // Updated role map
                        $badgeColorMap = [1 => 'bg-danger', 3 => 'bg-primary', 4 => 'bg-success']; // Updated badge color map
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
                <!-- DEBUG: Contenuto di usersMap: {{ json_encode($usersMap) }} -->
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
                                <td>
                                    <!--
                                        DEBUG:
                                        - Acceptance ID: {{ $acceptance->id }}
                                        - User ID: {{ $acceptance->user_id }}
                                        - User ID presente in usersMap: {{ isset($usersMap[$acceptance->user_id]) ? 'Sì' : 'No' }}
                                        @if(isset($usersMap[$acceptance->user_id]))
                                        - Dati utente: {{ json_encode($usersMap[$acceptance->user_id]) }}
                                        @endif
                                    -->
                                    {{ $usersMap[$acceptance->user_id]['operatore'] ?? 'N/D' }}</td>
                                <td>{{ $acceptance->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if(in_array('test1', $acceptance->tests))
                                        {{-- Controlla se il risultato del test esiste tramite la relazione --}}
                                        @if($acceptance->testAResult)
                                            @php
                                                $isOwnerA = $acceptance->testAResult->operator_id == $currentUser['id'];
                                                $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
                                                $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
                                            @endphp
                                            @if($acceptance->testAResult->rl_signature_id) {{-- 1. Validato da RL --}}
                                                <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-success mb-1" title="Visualizza Test A (Validato)">
                                                    <i class="fas fa-user-check"></i> Test A
                                                </a>
                                            @elseif($acceptance->testAResult->lab_signed_at) {{-- 2. Firmato da Tecnico --}}
                                                <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-primary mb-1" title="Visualizza Test A (Firmato)">
                                                    <i class="fas fa-signature"></i> Test A
                                                </a>
                                                @if($isLabManager)
                                                    <form action="{{ route('test-a.validate', $acceptance->testAResult) }}" method="POST" class="d-inline validate-form">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Valida Test A">
                                                            <i class="fas fa-user-check"></i> Valida
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                {{-- 3. Non firmato, non validato --}}
                                                @if($isOwnerA)
                                                    <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-warning mb-1" title="Modifica Risultati Test A">
                                                        <i class="fas fa-edit"></i> Test A
                                                    </a>
                                                    @if($isLabTechnician)
                                                        <form action="{{ route('test-a.sign', $acceptance->testAResult) }}" method="POST" class="d-inline sign-form">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Firma Test A">
                                                                <i class="fas fa-signature"></i> Firma
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test A">
                                                        <i class="fas fa-eye"></i> Test A
                                                    </a>
                                                @endif
                                            @endif
                                        @else
                                            {{-- 4. Non ancora eseguito --}}
                                            <a href="{{ route('test-a.create', $acceptance) }}" class="btn btn-sm btn-outline-primary mb-1" title="Esegui Test A: Controllo pH">
                                                <i class="fas fa-vial"></i> Test A
                                            </a>
                                        @endif
                                        {{-- Pulsante Cronologia per Test A --}}
                                        @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1 && $acceptance->testAResult)
                                                <a href="{{ route('history.show', ['modelNameShort' => 'test-a-result', 'id' => $acceptance->testAResult->id]) }}" class="btn btn-sm btn-outline-secondary mb-1" title="Cronologia Modifiche Test A">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                        @endif
                                    @endif
                                    @if(in_array('test2', $acceptance->tests))
                                        {{-- Controlla se il risultato del test esiste tramite la relazione --}}
                                        @if($acceptance->testBResult)
                                            @php
                                                $isOwner = $acceptance->testBResult->operator_id == $currentUser['id'];
                                                $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
                                                $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
                                            @endphp
                                            @if($acceptance->testBResult->rl_signature_id) {{-- 1. Validato da RL: Sola lettura per tutti --}}
                                                <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-success mb-1" title="Visualizza Test B (Validato)">
                                                    <i class="fas fa-user-check"></i> Test B
                                                </a>
                                            @elseif($acceptance->testBResult->lab_signed_at) {{-- 2. Firmato da Tecnico, non ancora validato da RL --}}
                                                <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-primary mb-1" title="Visualizza Test B (Firmato)">
                                                    <i class="fas fa-signature"></i> Test B
                                                </a>
                                                {{-- Se l'utente è un Responsabile Laboratorio (ruolo 4), può validare --}}
                                                @if($isLabManager)
                                                    <form action="{{ route('test-b.validate', $acceptance->testBResult) }}" method="POST" class="d-inline validate-form">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Valida Test B">
                                                            <i class="fas fa-user-check"></i> Valida
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                {{-- 3. Non firmato, non validato --}}
                                                @if($isOwner)
                                                    {{-- Il proprietario (tecnico) può modificare. --}}
                                                    <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-warning mb-1" title="Modifica Risultati Test B">
                                                        <i class="fas fa-edit"></i> Test B
                                                    </a>
                                                    {{-- Se il proprietario è anche un Tecnico di Laboratorio (ruolo 3), può firmare. --}}
                                                    @if($isLabTechnician)
                                                        <form action="{{ route('test-b.sign', $acceptance->testBResult) }}" method="POST" class="d-inline sign-form">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Firma Test B">
                                                                <i class="fas fa-signature"></i> Firma
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    {{-- Gli altri (inclusi RL se non è ancora firmato) possono solo visualizzare --}}
                                                    <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test B">
                                                        <i class="fas fa-eye"></i> Test B
                                                    </a>
                                                @endif
                                            @endif
                                        @else
                                            {{-- 4. Nessun risultato ancora inserito --}}
                                            <a href="{{ route('test-b.create', $acceptance) }}" class="btn btn-sm btn-outline-primary mb-1" title="Esegui Test B: Produttività">
                                                <i class="fas fa-vial"></i> Test B
                                            </a>
                                        @endif
                                        {{-- Pulsante Cronologia per Test B --}}
                                        @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1 && $acceptance->testBResult)
                                            <a href="{{ route('history.show', ['modelNameShort' => 'test-b-result', 'id' => $acceptance->testBResult->id]) }}" class="btn btn-sm btn-outline-secondary mb-1" title="Cronologia Modifiche Test B"><i class="fas fa-history"></i></a>
                                        @endif
                                    @endif
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
    {{-- Dipendenza per SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

            // Gestione conferma firma con SweetAlert2
            // Uso un event listener delegato per funzionare anche con la paginazione di DataTables
            $('#acceptancesTable').on('submit', 'form.sign-form', function(event) {
                event.preventDefault(); // Impedisce l'invio immediato del form
                var form = this;

                Swal.fire({
                    title: 'Sei sicuro?',
                    text: "Una volta firmato, il test non potrà più essere modificato. Vuoi procedere?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754', // Verde Bootstrap (success)
                    cancelButtonColor: '#6c757d',  // Grigio Bootstrap (secondary)
                    confirmButtonText: 'Sì, firma!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Se l'utente conferma, invia il form
                    }
                });
            });

            // Gestione conferma validazione con SweetAlert2
            $('#acceptancesTable').on('submit', 'form.validate-form', function(event) {
                event.preventDefault(); // Impedisce l'invio immediato del form
                var form = this;

                Swal.fire({
                    title: 'Sei sicuro di voler validare?',
                    text: "Questa azione è definitiva e confermerà i risultati del test. Il test non sarà più modificabile.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754', // Verde Bootstrap (success)
                    cancelButtonColor: '#6c757d',  // Grigio Bootstrap (secondary)
                    confirmButtonText: 'Sì, valida!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Se l'utente conferma, invia il form
                    }
                });
            });
        });
    </script>
</body>
</html>