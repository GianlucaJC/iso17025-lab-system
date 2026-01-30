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
                @if(!($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4))
                    <a href="{{ route('acceptance.create') }}" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i>Nuova Accettazione
                    </a>
                @endif
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
                @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('instruments.index') }}">Gestione Strumenti</a>
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('user-management.redirect') }}" target="_blank">
                        <i class="fas fa-users-cog me-1"></i>Gestione Utenti
                    </a>
                @endif
                <button id="show-docs-btn" class="btn btn-link nav-link d-inline-block align-middle ms-2">
                    <i class="fas fa-book me-1"></i>Guida ISO 17025
                </button>
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
                @if (session('calendarUrl'))
                    <hr>
                    <p class="mb-0">
                        <a href="{{ session('calendarUrl') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Aggiungi promemoria a Google Calendar
                        </a>
                        @if(session('reminderDays'))
                            @php $reminderDays = session('reminderDays'); @endphp
                            <small class="ms-2 text-muted">(Promemoria per la fine dell'incubazione tra {{ $reminderDays }} {{ $reminderDays == 1 ? 'giorno' : 'giorni' }})</small>
                        @endif
                    </p>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Messaggi di notifica per l'invio email --}}
        @if (session('notification_success'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-paper-plane me-2"></i>{{ session('notification_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('notification_error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('notification_error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('notification_warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('notification_warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="mb-3"><i class="fas fa-list-ul me-2"></i>Elenco Accettazioni Campioni</h3>
                {{-- Legenda Test Eseguiti --}}
                <div class="p-3 border rounded bg-light mt-3">
                    <h6 class="mb-2">Legenda Test Eseguiti:</h6>
                    <ul class="list-unstyled mb-0 d-flex flex-wrap gap-3">
                        <li><span class="badge bg-secondary">Test A</span>: Test A (Controllo del pH)</li>
                        <li><span class="badge bg-secondary">Test B</span>: Test B (Produttività, Metodo Qualitativo)</li>
                        <li><span class="badge bg-secondary">Test C</span>: Test C (Controllo della contaminazione microbica)</li>
                        <li><span class="badge bg-info text-dark">2x</span>: Eseguito in doppio</li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                {{-- Filter section --}}
                @php
                    $isAnyFilterActive = $filterTestAStatus !== 'all' || $filterTestBStatus !== 'all' || $filterTestCStatus !== 'all';
                @endphp
                <div class="mb-4 p-3 border rounded">
                    <a class="d-block text-decoration-none" data-bs-toggle="collapse" href="#filtersCollapse" role="button" aria-expanded="{{ $isAnyFilterActive ? 'true' : 'false' }}" aria-controls="filtersCollapse">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-dark"><i class="fas fa-filter me-2"></i>Filtri</h4>
                            <i class="fas fa-chevron-down text-dark"></i>
                        </div>
                    </a>
                    <div class="collapse{{ $isAnyFilterActive ? ' show' : '' }}" id="filtersCollapse">
                        <hr class="mt-3 mb-3">
                        <form method="GET" action="{{ route('acceptance.index') }}" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="filter_test_a_status" class="form-label">Stato Test A</label>
                                <select class="form-select" id="filter_test_a_status" name="filter_test_a_status">
                                    <option value="all" {{ $filterTestAStatus == 'all' ? 'selected' : '' }}>Tutti gli stati</option>
                                    <option value="not_compiled" {{ $filterTestAStatus == 'not_compiled' ? 'selected' : '' }}>Non compilato</option>
                                    <option value="in_compilation" {{ $filterTestAStatus == 'in_compilation' ? 'selected' : '' }}>In compilazione</option>
                                    <option value="signed" {{ $filterTestAStatus == 'signed' ? 'selected' : '' }}>Firmato dal Tecnico</option>
                                    <option value="validated" {{ $filterTestAStatus == 'validated' ? 'selected' : '' }}>Validato da RL</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_test_b_status" class="form-label">Stato Test B</label>
                                <select class="form-select" id="filter_test_b_status" name="filter_test_b_status">
                                    <option value="all" {{ $filterTestBStatus == 'all' ? 'selected' : '' }}>Tutti gli stati</option>
                                    <option value="not_compiled" {{ $filterTestBStatus == 'not_compiled' ? 'selected' : '' }}>Non compilato</option>
                                    <option value="in_compilation" {{ $filterTestBStatus == 'in_compilation' ? 'selected' : '' }}>In compilazione (parziale)</option>
                                    <option value="ready_to_sign" {{ $filterTestBStatus == 'ready_to_sign' ? 'selected' : '' }}>Pronto per Firma</option>
                                    <option value="signed" {{ $filterTestBStatus == 'signed' ? 'selected' : '' }}>Firmato dal Tecnico</option>
                                    <option value="validated" {{ $filterTestBStatus == 'validated' ? 'selected' : '' }}>Validato da RL</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_test_c_status" class="form-label">Stato Test C</label>
                                <select class="form-select" id="filter_test_c_status" name="filter_test_c_status">
                                    <option value="all" {{ $filterTestCStatus == 'all' ? 'selected' : '' }}>Tutti gli stati</option>
                                    <option value="not_compiled" {{ $filterTestCStatus == 'not_compiled' ? 'selected' : '' }}>Non compilato</option>
                                    <option value="in_compilation" {{ $filterTestCStatus == 'in_compilation' ? 'selected' : '' }}>In compilazione (parziale)</option>
                                    <option value="ready_to_sign" {{ $filterTestCStatus == 'ready_to_sign' ? 'selected' : '' }}>Pronto per Firma</option>
                                    <option value="signed" {{ $filterTestCStatus == 'signed' ? 'selected' : '' }}>Firmato dal Tecnico</option>
                                    <option value="validated" {{ $filterTestCStatus == 'validated' ? 'selected' : '' }}>Validato da RL</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label visually-hidden">Azioni</label> {{-- Empty label for alignment --}}
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Applica Filtri</button>
                                <a href="{{ route('acceptance.index') }}" class="btn btn-secondary ms-2"><i class="fas fa-redo me-2"></i>Reset Filtri</a>
                            </div>
                        </form>
                    </div>
                </div>

                <table id="acceptancesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>N. Accettazione</th>
                            <th>Lotto</th>
                            <th>Data Accettazione</th>
                            <th>Test Eseguiti</th>
                            <th>Data Creazione</th>
                            <th>Stato Test A</th>
                            <th>Stato Test B</th>
                            <th>Stato Test C</th>
                            <th>Esecuzione Test</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $testNameMap = [
                                'test1' => 'Test A',
                                'test2' => 'Test B',
                                'test3' => 'Test C'
                            ];
                            $fullTestNameMap = [ // Mappa completa per la legenda
                                'test1' => 'Test A (Controllo del pH)',
                                'test2' => 'Test B (Produttività, Metodo Qualitativo)',
                                'test3' => 'Test C (Controllo della contaminazione microbica)'
                            ];
                            $currentUser = Session::get('user');
                        @endphp
                        @foreach ($acceptances as $acceptance)
                            @php
                                // Define common variables for the entire row to ensure they are always available
                                $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
                                $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
                                $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
                            @endphp
                            <tr>
                                <td>{{ $acceptance->acceptance_number }}</td>
                                <td>{{ $acceptance->lotto }}</td>
                                <td>{{ \Carbon\Carbon::parse($acceptance->acceptance_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if(!empty($acceptance->tests))
                                        @foreach($acceptance->tests as $testKey)
                                            <div class="mb-1 text-nowrap">
                                                <span class="badge bg-secondary">{{ $testNameMap[$testKey] ?? $testKey }}</span>
                                                @if(is_array($acceptance->double_tests) && in_array($testKey, $acceptance->double_tests))
                                                    <span class="badge bg-info text-dark" title="Eseguito in doppio">2x</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                
                                <td>{{ $acceptance->created_at->format('d/m/Y H:i') }}</td>

                                {{-- Stato Test A --}}
                                <td>
                                    @if(in_array('test1', $acceptance->tests))
                                        @switch($acceptance->test_a_status)
                                            @case('validated')
                                                <span class="badge bg-success"><i class="fas fa-check-double me-1"></i>Validato da RL</span>
                                                @break
                                            @case('signed')
                                                <span class="badge bg-primary"><i class="fas fa-signature me-1"></i>Firmato dal Tecnico</span>
                                                @break
                                            @case('ready_to_sign')
                                                <span class="badge bg-info text-dark"><i class="fas fa-pencil-alt me-1"></i>Pronto per Firma</span>
                                                @break
                                            @case('in_compilation')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>In compilazione</span>
                                                @break
                                            @case('ready_to_sign')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>In compilazione</span>
                                                @break
                                            @case('not_compiled')
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Non compilato</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">N/D</span>
                                        @endswitch
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>

                                {{-- Stato Test B --}}
                                <td>
                                    @if(in_array('test2', $acceptance->tests))
                                        @switch($acceptance->test_b_status)
                                            @case('validated')
                                                <span class="badge bg-success"><i class="fas fa-check-double me-1"></i>Validato da RL</span>
                                                @break
                                            @case('signed')
                                                <span class="badge bg-primary"><i class="fas fa-signature me-1"></i>Firmato dal Tecnico</span>
                                                @break
                                            @case('ready_to_sign')
                                                <span class="badge bg-info text-dark"><i class="fas fa-pencil-alt me-1"></i>Pronto per Firma</span>
                                                @break
                                            @case('in_compilation')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>In compilazione</span>
                                                @break
                                            @case('not_compiled')
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Non compilato</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">N/D</span>
                                        @endswitch
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>

                                {{-- Stato Test C --}}
                                <td>
                                    @if(in_array('test3', $acceptance->tests))
                                        @switch($acceptance->test_c_status)
                                            @case('validated')
                                                <span class="badge bg-success"><i class="fas fa-check-double me-1"></i>Validato da RL</span>
                                                @break
                                            @case('signed')
                                                <span class="badge bg-primary"><i class="fas fa-signature me-1"></i>Firmato dal Tecnico</span>
                                                @break
                                            @case('ready_to_sign')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>In compilazione</span>
                                                @break
                                            @case('not_compiled')
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Non compilato</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">N/D</span>
                                        @endswitch
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($acceptance->sample_conformity === 'conforme')
                                        @if(in_array('test1', $acceptance->tests))
                                            @php
                                                $isOwnerA = $acceptance->testAResult && $acceptance->testAResult->operator_id == $currentUser['id'];
                                            @endphp
                                            @switch($acceptance->test_a_status)
                                                @case('validated')
                                                    <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-success mb-1" title="Visualizza Test A (Validato)"><i class="fas fa-user-check"></i> Test A</a>
                                                    @break
                                                @case('signed')
                                                    <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-primary mb-1" title="Visualizza Test A (Firmato)"><i class="fas fa-signature"></i> Test A</a>
                                                    @break
                                                @case('ready_to_sign')
                                                    @if($isOwnerA && !$isAdmin)
                                                        <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-warning mb-1" title="Modifica Risultati Test A"><i class="fas fa-edit"></i> Test A</a>
                                                        @if($isLabTechnician)
                                                            <form action="{{ route('test-a.sign', $acceptance->testAResult) }}" method="POST" class="d-inline sign-form">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Firma Test A"><i class="fas fa-signature"></i> Firma</button>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('test-a.edit', $acceptance->testAResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test A"><i class="fas fa-eye"></i> Test A</a>
                                                    @endif
                                                    @break
                                                @case('not_compiled')
                                                    @if($isLabTechnician && !$isAdmin)
                                                        <a href="{{ route('test-a.create', $acceptance) }}" class="btn btn-sm btn-outline-primary mb-1" title="Esegui Test A: Controllo pH"><i class="fas fa-vial"></i> Test A</a>
                                                    @endif
                                                    @break
                                            @endswitch
                                            {{-- Pulsante Cronologia per Test A --}}
                                            @if($isAdmin && $acceptance->testAResult)
                                                <a href="{{ route('history.show', ['modelNameShort' => 'test-a-result', 'id' => $acceptance->testAResult->id]) }}" class="btn btn-sm btn-outline-secondary mb-1" title="Cronologia Modifiche Test A"><i class="fas fa-history"></i></a>
                                            @endif
                                        @endif
                                        @if(in_array('test2', $acceptance->tests))
                                            @php
                                                $isOwnerB = $acceptance->testBResult && $acceptance->testBResult->operator_id == $currentUser['id'];
                                            @endphp
                                            @switch($acceptance->test_b_status)
                                                @case('validated')
                                                    <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-success mb-1" title="Visualizza Test B (Validato)"><i class="fas fa-user-check"></i> Test B</a>
                                                    @break
                                                @case('signed')
                                                    <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-primary mb-1" title="Visualizza Test B (Firmato)"><i class="fas fa-signature"></i> Test B</a>
                                                    @break
                                                @case('ready_to_sign')
                                                    @if($isOwnerB && !$isAdmin)
                                                        <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-warning mb-1" title="Modifica Risultati Test B"><i class="fas fa-edit"></i> Test B</a>
                                                        @if($isLabTechnician)
                                                            <form action="{{ route('test-b.sign', $acceptance->testBResult) }}" method="POST" class="d-inline sign-form">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Firma Test B"><i class="fas fa-signature"></i> Firma</button>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test B"><i class="fas fa-eye"></i> Test B</a>
                                                    @endif
                                                    @break
                                                @case('in_compilation')
                                                    @if($isOwnerB && !$isAdmin)
                                                        <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-warning mb-1" title="Completa/Modifica Risultati Test B"><i class="fas fa-edit"></i> Test B</a>
                                                    @else
                                                        <a href="{{ route('test-b.edit', $acceptance->testBResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test B"><i class="fas fa-eye"></i> Test B</a>
                                                    @endif
                                                    @break
                                                @case('not_compiled')
                                                    @if($isLabTechnician && !$isAdmin)
                                                        <a href="{{ route('test-b.create', $acceptance) }}" class="btn btn-sm btn-outline-primary mb-1" title="Esegui Test B: Produttività"><i class="fas fa-vial"></i> Test B</a>
                                                    @endif
                                                    @break
                                            @endswitch
                                            {{-- Pulsante Cronologia per Test B --}}
                                            @if($isAdmin && $acceptance->testBResult)
                                                <a href="{{ route('history.show', ['modelNameShort' => 'test-b-result', 'id' => $acceptance->testBResult->id]) }}" class="btn btn-sm btn-outline-secondary mb-1" title="Cronologia Modifiche Test B"><i class="fas fa-history"></i></a>
                                            @endif
                                        @endif
                                        @if(in_array('test3', $acceptance->tests))
                                            @php
                                                $isOwnerC = $acceptance->testCResult && $acceptance->testCResult->operator_id == $currentUser['id'];
                                            @endphp
                                            @switch($acceptance->test_c_status)
                                                @case('validated')
                                                    <a href="{{ route('test-c.edit', $acceptance->testCResult) }}" class="btn btn-sm btn-success mb-1" title="Visualizza Test C (Validato)"><i class="fas fa-user-check"></i> Test C</a>
                                                    @break
                                                @case('signed')
                                                    <a href="{{ route('test-c.edit', $acceptance->testCResult) }}" class="btn btn-sm btn-primary mb-1" title="Visualizza Test C (Firmato)"><i class="fas fa-signature"></i> Test C</a>
                                                    @break
                                                @case('ready_to_sign')
                                                    @if($isOwnerC && !$isAdmin)
                                                        <a href="{{ route('test-c.edit', $acceptance->testCResult) }}" class="btn btn-sm btn-warning mb-1" title="Modifica Risultati Test C"><i class="fas fa-edit"></i> Test C</a>
                                                        @if($isLabTechnician)
                                                            <form action="{{ route('test-c.sign', $acceptance->testCResult) }}" method="POST" class="d-inline sign-form">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Firma Test C"><i class="fas fa-signature"></i> Firma</button>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('test-c.edit', $acceptance->testCResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test C"><i class="fas fa-eye"></i> Test C</a>
                                                    @endif
                                                    @break
                                                @case('in_compilation')
                                                    @if($isOwnerC && !$isAdmin)
                                                        <a href="{{ route('test-c.edit', $acceptance->testCResult) }}" class="btn btn-sm btn-warning mb-1" title="Completa/Modifica Risultati Test C"><i class="fas fa-edit"></i> Test C</a>
                                                    @else
                                                        <a href="{{ route('test-c.edit', $acceptance->testCResult) }}" class="btn btn-sm btn-info mb-1" title="Visualizza Risultati Test C"><i class="fas fa-eye"></i> Test C</a>
                                                    @endif
                                                    @break
                                                @case('not_compiled')
                                                    @if($isLabTechnician && !$isAdmin)
                                                        <a href="{{ route('test-c.create', $acceptance) }}" class="btn btn-sm btn-outline-primary mb-1" title="Esegui Test C: Controllo contaminazione microbica"><i class="fas fa-vial"></i> Test C</a>
                                                    @endif
                                                    @break
                                            @endswitch
                                            {{-- Pulsante Cronologia per Test C --}}
                                            @if($isAdmin && $acceptance->testCResult)
                                                <a href="{{ route('history.show', ['modelNameShort' => 'test-c-result', 'id' => $acceptance->testCResult->id]) }}" class="btn btn-sm btn-outline-secondary mb-1" title="Cronologia Modifiche Test C"><i class="fas fa-history"></i></a>
                                            @endif
                                        @endif
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Non Conforme</span>
                                    @endif
                                </td>
                                <td class="text-nowrap"> {{-- $isAdmin is already defined --}}
                                    @if($currentUser && ($acceptance->user_id == $currentUser['id']))
                                        @if(!$isAdmin)
                                            <a href="{{ route('acceptance.edit', $acceptance) }}" class="btn btn-primary btn-sm" title="Modifica">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('acceptance.edit', $acceptance) }}" class="btn btn-info btn-sm" title="Visualizza">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{ route('acceptance.edit', $acceptance) }}" class="btn btn-info btn-sm" title="Visualizza">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    {{-- Pulsante per scaricare il Rapporto di Prova (solo se il campione è conforme) --}}
                                    @if($acceptance->sample_conformity === 'conforme')
                                        @if($acceptance->is_pdf_complete)
                                            <a href="{{ route('acceptance.pdf', $acceptance->id) }}" class="btn btn-sm btn-success ms-1" title="Scarica Rapporto Finale PDF" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Finale
                                            </a>
                                        @else
                                            <a href="{{ route('acceptance.pdf', $acceptance->id) }}" class="btn btn-sm btn-info ms-1" title="Scarica Anteprima PDF" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Anteprima
                                            </a>
                                        @endif
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

            // Gestione modale documentazione
            $('#show-docs-btn').on('click', function() {
                Swal.fire({
                    title: '<strong>Manuale Utente e Conformità ISO/IEC 17025:2017</strong>',
                    icon: 'info',
                    html: `
                        <div class="text-start">
                            <p class="text-center">Questo software è stato progettato per supportare le operazioni del laboratorio in conformità con i requisiti della norma <strong>ISO/IEC 17025:2017</strong>, garantendo la tracciabilità, l'integrità dei dati e il controllo degli accessi.</p>
                            <hr>

                            <h5 class="mt-3"><i class="fas fa-users-cog me-2 text-primary"></i>Gestione Ruoli e Permessi</h5>
                            <p>Il sistema si basa su ruoli definiti per garantire che solo il personale autorizzato possa eseguire determinate azioni, come richiesto dalla norma:</p>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <span class="badge bg-primary">Tecnico di Laboratorio</span>: Responsabile della creazione delle accettazioni e dell'esecuzione/compilazione dei test. Può modificare solo i dati da lui inseriti, a patto che non siano stati ancora firmati. Appone la prima firma elettronica (<strong>Firma Tecnico</strong>).
                                </li>
                                <li class="list-group-item">
                                    <span class="badge bg-success">Responsabile Laboratorio</span>: Supervisiona i risultati. Non può creare o modificare dati operativi, ma ha il compito di validare i test già firmati dal tecnico, apponendo la seconda firma elettronica (<strong>Validazione RL</strong>).
                                </li>
                                <li class="list-group-item">
                                    <span class="badge bg-danger">Admin</span>: Ha visibilità completa su tutti i dati a scopo di audit e supervisione. Può visualizzare la cronologia delle modifiche ma non può alterare i dati di accettazione o i risultati dei test per preservare l'integrità del dato.
                                </li>
                            </ul>

                            <h5 class="mt-4"><i class="fas fa-project-diagram me-2 text-primary"></i>Flusso Operativo Controllato</h5>
                            <p>Il ciclo di vita di un test segue un percorso rigoroso per assicurare la corretta revisione e approvazione dei dati:</p>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item"><strong>Accettazione</strong>: Il Tecnico crea una nuova scheda di accettazione.</li>
                                <li class="list-group-item"><strong>Esecuzione Test</strong>: Il Tecnico esegue un test e ne salva i risultati. Lo stato passa a <span class="badge bg-warning text-dark">In compilazione</span>.</li>
                                <li class="list-group-item"><strong>Firma del Tecnico</strong>: Una volta completato, il Tecnico firma il test. Questa azione blocca ogni modifica futura da parte sua. Lo stato passa a <span class="badge bg-primary">Firmato dal Tecnico</span>.</li>
                                <li class="list-group-item"><strong>Validazione RL</strong>: Il Responsabile Laboratorio revisiona il test firmato e, se corretto, lo valida. Questa è la seconda firma elettronica e rende il record <strong>immutabile</strong>. Lo stato passa a <span class="badge bg-success">Validato da RL</span>.</li>
                            </ol>

                            <h5 class="mt-4"><i class="fas fa-shield-alt me-2 text-primary"></i>Funzionalità Chiave per la Conformità</h5>
                            <dl>
                                <dt><i class="fas fa-history me-1"></i>Audit Trail (Cronologia)</dt>
                                <dd>
                                    Ogni modifica a una scheda di accettazione o a un risultato di test viene registrata. L'Admin può visualizzare la cronologia completa ( <button class="btn btn-secondary btn-sm py-0" disabled><i class="fas fa-history"></i></button> ) per ogni record, verificando <strong>chi</strong> ha modificato <strong>cosa</strong>, <strong>quando</strong> e <strong>perché</strong> (se richiesta una motivazione). Questo soddisfa il requisito di tracciabilità delle modifiche (punto 7.5 della norma).
                                </dd>

                                <dt class="mt-2"><i class="fas fa-signature me-1"></i>Firme Elettroniche e Integrità dei Dati</dt>
                                <dd>
                                    Il processo di firma a due livelli (Tecnico + RL) garantisce che i dati siano revisionati e approvati da personale autorizzato. Una volta validato, il record non è più modificabile, assicurando l'integrità e l'immodificabilità del dato finale come richiesto dalla norma (punto 7.11).
                                </dd>

                                <dt class="mt-2"><i class="fas fa-user-lock me-1"></i>Controllo degli Accessi</dt>
                                <dd>
                                    Il sistema limita le azioni in base al ruolo dell'utente loggato, impedendo a personale non autorizzato di eseguire operazioni critiche come la validazione dei risultati o la visualizzazione di dati sensibili come la cronologia (punto 6.2).
                                </dd>

                                <dt class="mt-2"><i class="fas fa-edit me-1"></i>Controllo delle Modifiche</dt>
                                <dd>
                                    Qualsiasi modifica a un record esistente (sia accettazione che risultati di test) richiede l'inserimento obbligatorio di una <strong>motivazione</strong>. Questa informazione viene salvata nell'Audit Trail, fornendo una giustificazione chiara per ogni cambiamento apportato ai dati.
                                </dd>
                            </dl>
                        </div>
                    `,
                    showCloseButton: true,
                    showCancelButton: false,
                    focusConfirm: false,
                    confirmButtonText: '<i class="fa fa-thumbs-up"></i> Ho capito!',
                    confirmButtonAriaLabel: 'Thumbs up, great!',
                    width: '80%',
                });
            });
        });
    </script>
</body>
</html>