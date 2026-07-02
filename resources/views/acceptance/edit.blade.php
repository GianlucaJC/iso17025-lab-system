<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        // Determina se la vista è in modalità di sola lettura
        $is_readonly = $is_readonly ?? false;
    @endphp
    <title>{{ $is_readonly ? 'Visualizza' : 'Modifica' }} Accettazione</title>
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
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div>
                @if(!in_array($currentUser['user17025'] ?? null, [1, 4, 5]))
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
                        $roleMap = [1 => 'Admin', 3 => 'Tecnico di Laboratorio', 4 => 'Responsabile Laboratorio', 5 => 'Responsabile Assicurazione Qualità'];
                        $badgeColorMap = [1 => 'bg-danger', 3 => 'bg-primary', 4 => 'bg-success', 5 => 'bg-info text-dark'];
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
        <div class="card">
            <div class="card-header">
                <h3>
                    @if($is_readonly)
                        <i class="fas fa-eye me-2"></i>Visualizza Scheda di Accettazione Campioni
                    @else
                        <i class="fas fa-edit me-2"></i>Modifica Scheda di Accettazione Campioni
                    @endif
                </h3>
            </div>
            <div class="card-body">
                {{-- Mostra errori di validazione --}}
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('acceptance.update', $acceptance->id) }}" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')

                    {{-- SEZIONE DATI GENERALI --}}
                    <h5>Dati Generali</h5>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="acceptance_number" class="form-label">Numero Accettazione</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                <input type="text" class="form-control" id="acceptance_number" name="acceptance_number" value="{{ old('acceptance_number', $acceptance->acceptance_number) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                <div class="invalid-feedback">Per favore, inserisci il numero di accettazione.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="lotto" class="form-label">Lotto</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                <input type="text" class="form-control" id="lotto" name="lotto" value="{{ old('lotto', $acceptance->lotto) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                <div class="invalid-feedback">Per favore, inserisci il lotto.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="sampling_date" class="form-label">Data Campionamento</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="sampling_date" name="sampling_date" value="{{ old('sampling_date', \Carbon\Carbon::parse($acceptance->sampling_date)->format('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                <button class="btn btn-outline-secondary" type="button" id="calculate-sampling-date-btn" title="Calcola data da lotto" {{ $is_readonly ? 'disabled' : '' }}><i class="fas fa-calculator"></i></button>
                                <div class="invalid-feedback">Per favore, inserisci la data di campionamento.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="acceptance_date" class="form-label">Data Accettazione</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                <input type="date" class="form-control" id="acceptance_date" name="acceptance_date" value="{{ old('acceptance_date', \Carbon\Carbon::parse($acceptance->acceptance_date)->format('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </div>

                    {{-- SEZIONE CONFORMITÀ CAMPIONE --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Conformità Campione</legend>
                        <div class="row g-3 p-3 bg-light border rounded">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sample_conformity" id="sample_conformity_conforme" value="conforme" {{ old('sample_conformity', $acceptance->sample_conformity) == 'conforme' ? 'checked' : '' }} required {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="sample_conformity_conforme">
                                        <i class="fas fa-check-circle text-success me-1"></i> Conforme
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sample_conformity" id="sample_conformity_non_conforme" value="non_conforme" {{ old('sample_conformity', $acceptance->sample_conformity) == 'non_conforme' ? 'checked' : '' }} required {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="sample_conformity_non_conforme">
                                        <i class="fas fa-times-circle text-danger me-1"></i> Non Conforme
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 mt-3" id="non-conformity-reason-section" style="display: none;">
                                <label for="non_conformity_reason" class="form-label">Motivazione Non Conformità</label>
                                <textarea class="form-control @error('non_conformity_reason') is-invalid @enderror" id="non_conformity_reason" name="non_conformity_reason" rows="3" placeholder="Specificare la motivazione della non conformità" {{ old('sample_conformity', $acceptance->sample_conformity) == 'non_conforme' ? 'required' : '' }} {{ $is_readonly ? 'disabled' : '' }}>{{ old('non_conformity_reason', $acceptance->non_conformity_reason) }}</textarea>
                                @error('non_conformity_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="invalid-feedback">La motivazione della non conformità è obbligatoria.</div>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- SEZIONE TEST DA ESEGUIRE --}}
                    <h5>Test da Eseguire</h5>
                    <div class="row mb-4">
                        @php
                            $selectedTests = old('tests', $acceptance->tests ?? []);
                            $doubleTests = old('double_tests', $acceptance->double_tests ?? []);
                        @endphp
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test1" id="test1" name="tests[]" @if(in_array('test1', $selectedTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="test1">
                                    MA_09_Misurazione del pH <span class="text-muted">({{ $methodRevisions['test_a']->revision_string ?? 'N/D' }})</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-3" id="double-test1-container" style="display: none;">
                                <input class="form-check-input double-test-checkbox" type="checkbox" value="test1" id="double_test1" name="double_tests[]" @if(in_array('test1', $doubleTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label" for="double_test1">Esegui in doppio</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test2" id="test2" name="tests[]" @if(in_array('test2', $selectedTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="test2">
                                    MA_61_Contaminazione microbica <span class="text-muted">({{ $methodRevisions['test_b']->revision_string ?? 'N/D' }})</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-3" id="double-test2-container" style="display: none;">
                                <input class="form-check-input double-test-checkbox" type="checkbox" value="test2" id="double_test2" name="double_tests[]" @if(in_array('test2', $doubleTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label" for="double_test2">Esegui in doppio</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test3" id="test3" name="tests[]" @if(in_array('test3', $selectedTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="test3">
                                    MA_60_Valutazione produttività XLD <span class="text-muted">({{ $methodRevisions['test_c']->revision_string ?? 'N/D' }})</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-3" id="double-test3-container" style="display: none;">
                                <input class="form-check-input double-test-checkbox" type="checkbox" value="test3" id="double_test3" name="double_tests[]" @if(in_array('test3', $doubleTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label" for="double_test3">Esegui in doppio</label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none" id="no-test-selected-alert">
                        Seleziona almeno un test per procedere.
                    </div>

                    {{-- SEZIONE IDENTIFICAZIONE PIASTRE --}}
                    <h5>Identificazione Piastre</h5>
                    <div id="plates-section">
                        @php
                            $plates = old('plates', $acceptance->plates ?? array_fill(0, 40, null));
                            $test_definitions = [
                                'test1' => [
                                    'name' => 'MA_09_Misurazione del pH (' . ($methodRevisions['test_a']->revision_string ?? 'N/D') . ')',
                                    'std_plates' => [
                                        0 => 'ID Piastra', // Solo la piastra principale
                                    ],
                                    'dbl_plates' => [
                                        2 => 'ID Piastra (Doppio)', // Solo la piastra principale in doppio
                                    ],
                                ],
                                'test2' => [
                                    'name' => 'MA_61_Contaminazione microbica (' . ($methodRevisions['test_b']->revision_string ?? 'N/D') . ')',
                                    'std_plates' => [
                                        '35°C' => [
                                            '1° Gruppo Piastre' => [4, 5],
                                            '2° Gruppo Piastre' => [6, 7],
                                            '3° Gruppo Piastre' => [8, 9],
                                        ],
                                        '22°C' => [
                                            '1° Gruppo Piastre' => [10, 11],
                                            '2° Gruppo Piastre' => [12, 13],
                                            '3° Gruppo Piastre' => [14, 15],
                                        ],
                                    ],
                                    'dbl_plates' => [
                                        '35°C' => [
                                            '1° Gruppo Piastre' => [16, 17],
                                            '2° Gruppo Piastre' => [18, 19],
                                            '3° Gruppo Piastre' => [20, 21],
                                        ],
                                        '22°C' => [
                                            '1° Gruppo Piastre' => [22, 23],
                                            '2° Gruppo Piastre' => [24, 25],
                                            '3° Gruppo Piastre' => [26, 27],
                                        ],
                                    ],
                                ],
                                'test3' => [
                                    'name' => 'MA_60_Valutazione produttività XLD (' . ($methodRevisions['test_c']->revision_string ?? 'N/D') . ')',
                                    'std_plates' => [
                                        28 => 'ID Piastra 1',
                                        29 => 'ID Piastra 2',
                                        30 => 'ID Piastra 3',
                                        31 => 'ID Piastra Controllo Bianco',
                                        36 => 'ID Piastra Controllo TSA',
                                    ],
                                    'dbl_plates' => [
                                        32 => 'ID Piastra 1 (Doppio)',
                                        33 => 'ID Piastra 2 (Doppio)',
                                        34 => 'ID Piastra 3 (Doppio)',
                                        35 => 'ID Piastra Controllo Bianco (Doppio)',
                                        37 => 'ID Piastra Controllo TSA (Doppio)',
                                    ],
                                ],
                            ];
                        @endphp
                        @foreach ($test_definitions as $test_id => $def)
                            <fieldset id="plates-group-{{ $test_id }}" class="mb-4 p-3 border rounded d-none" data-test-id="{{ $test_id }}">
                                <legend class="h6 w-auto px-2 bg-primary-subtle text-primary-emphasis rounded">{{ $def['name'] }}</legend>

                                {{-- Standard Plates --}}
                                <div class="row standard-plates-row">
                                    @if($test_id === 'test2')
                                        {{-- Layout speciale per MA_61_Contaminazione microbica --}}
                                        @foreach($def['std_plates'] as $temp => $positions)
                                            <div class="col-md-6 mb-3">
                                                <div class="p-2 border rounded h-100">
                                                    <h6 class="text-muted">{{ $temp }}</h6>
                                                    @foreach($positions as $position => $indices)
                                                        <div class="row mb-2 align-items-center">
                                                            <div class="col-sm-3"><label class="form-label mb-0">{{ $position }}</label></div>
                                                            @foreach($indices as $k => $i)
                                                                <div class="col-sm-4">
                                                                    <div class="input-group has-validation">
                                                                        <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                                        <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="P{{$k+1}}" value="{{ $plates[$i] ?? '' }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                                        <div class="invalid-feedback">ID Obbligatorio.</div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        {{-- Layout generico per MA_09_Misurazione del pH e MA_60_Valutazione produttività XLD --}}
                                        @foreach($def['std_plates'] as $i => $label)
                                            @if($test_id === 'test3' && $i == 36)
                                                <div class="col-md-4 mb-2">
                                                    <label for="plate_{{ $i }}_id" class="form-label">{{ $label }}</label>
                                                    <div class="input-group has-validation">
                                                        <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                        @php
                                                            $plateValue = isset($plates[$i]) && is_array($plates[$i]) ? $plates[$i] : [];
                                                        @endphp
                                                        <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}_id" name="plates[{{ $i }}][id]" placeholder="ID (numeri)" value="{{ old('plates.'.$i.'.id', $plateValue['id'] ?? '') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                        <input type="text" class="form-control plate-input" id="plate_{{ $i }}_lot" name="plates[{{ $i }}][lot]" placeholder="Lotto" value="{{ old('plates.'.$i.'.lot',  $plateValue['lot'] ?? '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                                        <div class="invalid-feedback">ID Piastra e Lotto sono obbligatori.</div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="col-md-4 mb-2">
                                                    <label for="plate_{{ $i }}" class="form-label">{{ $label }}</label>
                                                    <div class="input-group has-validation">
                                                        <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                        <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="Solo numeri" value="{{ old('plates.'.$i, $plates[$i] ?? '') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                        <div class="invalid-feedback">L'ID Piastra è obbligatorio.</div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>

                                {{-- Double Plates --}}
                                <div id="double-plates-group-{{ $test_id }}" class="d-none double-plates-container" data-test-id="{{ $test_id }}">
                                    <hr class="my-3">
                                    <h6 class="text-muted mb-3">Piastre per test in doppio</h6>
                                    <div class="row">
                                        @if($test_id === 'test2')
                                            {{-- Layout speciale per MA_61_Contaminazione microbica in doppio --}}
                                            @foreach($def['dbl_plates'] as $temp => $positions)
                                                <div class="col-md-6 mb-3">
                                                    <div class="p-2 border rounded h-100">
                                                        <h6 class="text-muted">{{ $temp }}</h6>
                                                        @foreach($positions as $position => $indices)
                                                            <div class="row mb-2 align-items-center">
                                                                <div class="col-sm-3"><label class="form-label mb-0">{{ $position }}</label></div>
                                                                @foreach($indices as $k => $i)
                                                                    <div class="col-sm-4">
                                                                        <div class="input-group has-validation">
                                                                            <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="P{{$k+1}}" value="{{ $plates[$i] ?? '' }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                                            <div class="invalid-feedback">ID Obbligatorio.</div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Layout generico per MA_09_Misurazione del pH e MA_60_Valutazione produttività XLD in doppio --}}
                                            @foreach($def['dbl_plates'] as $i => $label)
                                                @if($test_id === 'test3' && $i == 37)
                                                    <div class="col-md-4 mb-2">
                                                        <label for="plate_{{ $i }}_id" class="form-label">{{ $label }}</label>
                                                        <div class="input-group has-validation">
                                                            <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}_id" name="plates[{{ $i }}][id]" placeholder="ID (numeri)" value="{{ old('plates.'.$i.'.id', (isset($plates[$i]) && is_array($plates[$i])) ? ($plates[$i]['id'] ?? '') : '') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                            <input type="text" class="form-control plate-input" id="plate_{{ $i }}_lot" name="plates[{{ $i }}][lot]" placeholder="Lotto" value="{{ old('plates.'.$i.'.lot', (isset($plates[$i]) && is_array($plates[$i])) ? ($plates[$i]['lot'] ?? '') : '') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                            <div class="invalid-feedback">ID Piastra e Lotto sono obbligatori.</div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="col-md-4 mb-2">
                                                        <label for="plate_{{ $i }}" class="form-label">{{ $label }}</label>
                                                        <div class="input-group has-validation">
                                                            <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="Solo numeri" value="{{ old('plates.'.$i, $plates[$i] ?? '') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" {{ $is_readonly ? 'disabled' : '' }}>
                                                            <div class="invalid-feedback">L'ID Piastra è obbligatorio.</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    {{-- SEZIONE MOTIVAZIONE MODIFICA (solo in edit mode) --}}
                    @if(!$is_readonly)
                    <fieldset class="mb-4">
                        <legend class="h5">Motivazione della Modifica</legend>
                        <div class="form-group">
                            <label for="modification_reason" class="form-label">La modifica di una scheda già salvata richiede una motivazione (min. 10 caratteri).</label>
                            <textarea class="form-control @error('modification_reason') is-invalid @enderror" id="modification_reason" name="modification_reason" rows="3" required minlength="10">{{ old('modification_reason') }}</textarea>
                            @error('modification_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">Per favore, inserisci una motivazione per la modifica.</div>
                            @enderror
                        </div>
                    </fieldset>
                    @endif

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('acceptance.index') }}" class="btn btn-secondary btn-lg">
                            @if($is_readonly)
                                <i class="fas fa-arrow-left me-2"></i>Torna indietro
                            @else
                                <i class="fas fa-times me-2"></i>Annulla
                            @endif
                        </a>
                        @if(!$is_readonly)
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Salva Modifiche</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </main>

    {{-- Script per attivare la validazione Bootstrap (identico a create.blade.php) --}}
    <script>
        (function () {
          'use strict'
          var forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                let isValid = form.checkValidity(); // Check Bootstrap's built-in validation
                let customValidationPassed = true;

                const testCheckboxes = form.querySelectorAll('.test-checkbox');
                const noTestSelectedAlert = document.getElementById('no-test-selected-alert');
                let anyTestSelected = Array.from(testCheckboxes).some(cb => cb.checked);

                if (!anyTestSelected) {
                    // Only show alert if sample is 'conforme' and no tests are selected
                    const isSampleConforme = document.getElementById('sample_conformity_conforme').checked;
                    if (isSampleConforme) {
                        noTestSelectedAlert.classList.remove('d-none');
                    }
                    // If sample is 'non_conforme', it's fine not to have tests selected
                    customValidationPassed = false;
                } else {
                    noTestSelectedAlert.classList.add('d-none');
                }

                const plateInputs = form.querySelectorAll('input[name^="plates["]');
                
                plateInputs.forEach(input => { // Reset custom validity messages
                    input.setCustomValidity('');
                    input.classList.remove('is-invalid');
                });

                testCheckboxes.forEach((checkbox) => {
                    if (checkbox.checked) {
                        const testId = checkbox.value;
                        const doubleCheckbox = document.getElementById('double_' + testId);

                        const plateGroup = document.getElementById('plates-group-' + testId);
                        // Only validate plate inputs if the test checkbox is enabled (i.e., sample is conforme)
                        if (plateGroup && !checkbox.disabled) {
                            // Validate standard plates (only if the test is selected and not disabled)
                            if (checkbox.checked) {
                                const standardInputs = plateGroup.querySelectorAll('.standard-plates-row .plate-input');
                                standardInputs.forEach(plateInput => {
                                    if (plateInput.value.trim() === '') {
                                        plateInput.setCustomValidity('Questo campo è obbligatorio per il test selezionato.');
                                        plateInput.classList.add('is-invalid');
                                        customValidationPassed = false;
                                    }
                                });

                                // Validate double plates if checked (only if the double checkbox is enabled)
                                if (doubleCheckbox && doubleCheckbox.checked && !doubleCheckbox.disabled) {
                                    const doubleInputs = plateGroup.querySelectorAll('.double-plates-container .plate-input');
                                    doubleInputs.forEach(plateInput => {
                                        if (plateInput.value.trim() === '') {
                                            plateInput.setCustomValidity('Questo campo è obbligatorio per il test selezionato.');
                                            plateInput.classList.add('is-invalid');
                                            customValidationPassed = false;
                                        }
                                    });
                                }

                            }
                        }
                    }
                });

                if (!isValid || !customValidationPassed) {
                  event.preventDefault()
                  event.stopPropagation()
                }
        
                form.classList.add('was-validated')
              }, false)
            })
        })()
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const testCheckboxes = document.querySelectorAll('.test-checkbox');
            const doubleTestCheckboxes = document.querySelectorAll('.double-test-checkbox');
            const noTestSelectedAlert = document.getElementById('no-test-selected-alert');

            function updatePlateVisibility() { // This function now also considers the disabled state
                testCheckboxes.forEach((checkbox) => {
                    const testId = checkbox.value;
                    const doubleContainer = document.getElementById('double-' + testId + '-container');
                    const doubleCheckbox = document.getElementById('double_' + testId);
                    const plateGroup = document.getElementById('plates-group-' + testId);
                    const doublePlateGroup = document.getElementById('double-plates-group-' + testId);

                    if (checkbox.checked) {
                        if (doubleContainer) doubleContainer.style.display = 'inline-block';
                        if (plateGroup) plateGroup.classList.remove('d-none');
                        // Only show double plate group if double checkbox is checked.
                        if (doubleCheckbox && doubleCheckbox.checked) {
                            if (doublePlateGroup) doublePlateGroup.classList.remove('d-none');
                        } else {
                            if (doublePlateGroup) doublePlateGroup.classList.add('d-none');
                        }
                    } else {
                        if (doubleContainer) doubleContainer.style.display = 'none';
                        if (plateGroup) plateGroup.classList.add('d-none');
                        // Non nascondere doublePlateGroup qui, è già dentro plateGroup
                        if (doubleCheckbox) doubleCheckbox.checked = false;
                    }
                });
            }

            testCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePlateVisibility);
            });
            doubleTestCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updatePlateVisibility);
            });

            // Initial call to set correct visibility on page load
            updatePlateVisibility();

            // --- Gestione Conformità Campione ---
            const conformityRadios = document.querySelectorAll('input[name="sample_conformity"]');
            const nonConformityReasonSection = document.getElementById('non-conformity-reason-section');
            const nonConformityReasonTextarea = document.getElementById('non_conformity_reason');

            function toggleConformityFields() {
                const isNonConforme = document.getElementById('sample_conformity_non_conforme').checked;
                const isFormReadonly = {{ $is_readonly ? 'true' : 'false' }};

                if (isNonConforme) {
                    nonConformityReasonSection.style.display = 'block';
                    if (!isFormReadonly) {
                        nonConformityReasonTextarea.setAttribute('required', 'required');
                    }
                    // Disable all test checkboxes if not readonly
                    testCheckboxes.forEach(cb => {
                        cb.checked = false; // Uncheck them
                        cb.disabled = true;
                    });
                    doubleTestCheckboxes.forEach(cb => {
                        cb.checked = false; // Uncheck them
                        cb.disabled = true;
                    });
                    updatePlateVisibility(); // Update plate visibility based on disabled checkboxes
                    noTestSelectedAlert.classList.add('d-none'); // Nascondi l'alert se presente
                } else {
                    nonConformityReasonSection.style.display = 'none';
                    nonConformityReasonTextarea.removeAttribute('required');
                    nonConformityReasonTextarea.value = ''; // Clear reason if it becomes conforme
                    // Enable all test checkboxes if not readonly
                    if (!isFormReadonly) {
                        testCheckboxes.forEach(cb => cb.disabled = false);
                        doubleTestCheckboxes.forEach(cb => cb.disabled = false);
                    }
                }
            }

            conformityRadios.forEach(radio => radio.addEventListener('change', toggleConformityFields));
            toggleConformityFields(); // Initial call for conformity fields on page load

            // --- Automazione Data Campionamento da Lotto ---
            const lottoInput = document.getElementById('lotto');
            const samplingDateInput = document.getElementById('sampling_date');
            const calculateBtn = document.getElementById('calculate-sampling-date-btn');

            if (lottoInput && samplingDateInput && calculateBtn) {
                calculateBtn.addEventListener('click', function() {
                    const lottoValue = lottoInput.value.trim();
                    
                    // Controlla se il lotto ha almeno 6 cifre numeriche
                    if (lottoValue.length >= 6 && /^\d{6}/.test(lottoValue)) {
                        const datePart = lottoValue.substring(0, 6);
                        const month = datePart.substring(0, 2);
                        const day = datePart.substring(2, 4);
                        const year = '20' + datePart.substring(4, 6); // Assumiamo anni del 21° secolo

                        // Costruiamo una data per validarla. Mese è 0-indexed.
                        const tempDate = new Date(parseInt(year, 10), parseInt(month, 10) - 1, parseInt(day, 10));
                        
                        if (tempDate.getFullYear() == year && (tempDate.getMonth() + 1) == parseInt(month, 10) && tempDate.getDate() == parseInt(day, 10)) {
                            const formattedDate = `${year}-${month}-${day}`;
                            samplingDateInput.value = formattedDate;
                        }
                    }
                });
            }
        });
    </script>

    <footer class="mt-auto text-center py-3 bg-light">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>
</body>
</html>
