<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Nuova Accettazione</title>
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
                        $roleMap = [
                            1 => 'Amministratore',
                            2 => 'Resp. Accettazione',
                            3 => 'Tecnico di Laboratorio',
                            4 => 'Resp. Qualità',
                        ];
                        $badgeColorMap = [
                            1 => 'bg-danger',
                            2 => 'bg-info text-dark',
                            3 => 'bg-primary',
                            4 => 'bg-success',
                        ];
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
                    <i class="fas fa-plus-circle me-2"></i>Nuova Scheda di Accettazione Campioni
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

                <form method="POST" action="{{ route('acceptance.store') }}" class="needs-validation" novalidate>
                    @csrf

                    {{-- SEZIONE DATI GENERALI --}}
                    <h5>Dati Generali</h5>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="acceptance_number" class="form-label">Numero Accettazione</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                <input type="text" class="form-control" id="acceptance_number" name="acceptance_number" value="{{ old('acceptance_number') }}" required>
                                <div class="invalid-feedback">Per favore, inserisci il numero di accettazione.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="lotto" class="form-label">Lotto</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                <input type="text" class="form-control" id="lotto" name="lotto" value="{{ old('lotto') }}" required>
                                <div class="invalid-feedback">Per favore, inserisci il lotto.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="sampling_date" class="form-label">Data Campionamento</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="sampling_date" name="sampling_date" value="{{ old('sampling_date', date('Y-m-d')) }}" required>
                                <div class="invalid-feedback">Per favore, inserisci la data di campionamento.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="acceptance_date" class="form-label">Data Accettazione</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                <input type="date" class="form-control" id="acceptance_date" name="acceptance_date" value="{{ old('acceptance_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>

                    {{-- SEZIONE TEST DA ESEGUIRE --}}
                    <h5>Test da Eseguire</h5>
                    <div class="row mb-4">
                        @php
                            $selectedTests = old('tests', []);
                            $doubleTests = old('double_tests', []);
                        @endphp
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test1" id="test1" name="tests[]" @if(in_array('test1', $selectedTests)) checked @endif>
                                <label class="form-check-label" for="test1">
                                    Test A (Controllo del pH)
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-3" id="double-test1-container" style="display: none;">
                                <input class="form-check-input double-test-checkbox" type="checkbox" value="test1" id="double_test1" name="double_tests[]" @if(in_array('test1', $doubleTests)) checked @endif>
                                <label class="form-check-label" for="double_test1">Esegui in doppio</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test2" id="test2" name="tests[]" @if(in_array('test2', $selectedTests)) checked @endif>
                                <label class="form-check-label" for="test2">
                                    Test B (Produttività, Metodo Qualitativo)
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-3" id="double-test2-container" style="display: none;">
                                <input class="form-check-input double-test-checkbox" type="checkbox" value="test2" id="double_test2" name="double_tests[]" @if(in_array('test2', $doubleTests)) checked @endif>
                                <label class="form-check-label" for="double_test2">Esegui in doppio</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test3" id="test3" name="tests[]" @if(in_array('test3', $selectedTests)) checked @endif>
                                <label class="form-check-label" for="test3">
                                    Test C (Controllo della contaminazione microbica)
                                </label>
                            </div>
                            <div class="form-check form-check-inline ms-3" id="double-test3-container" style="display: none;">
                                <input class="form-check-input double-test-checkbox" type="checkbox" value="test3" id="double_test3" name="double_tests[]" @if(in_array('test3', $doubleTests)) checked @endif>
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
                            $plates = old('plates', array_fill(0, 40, null));
                            $test_definitions = [
                                'test1' => [
                                    'name' => 'Test A (Controllo del pH)',
                                    'std_plates' => [
                                        0 => 'ID Piastra',
                                        1 => 'ID Piastra Controllo TSA',
                                    ],
                                    'dbl_plates' => [
                                        2 => 'ID Piastra (Doppio)',
                                        3 => 'ID Piastra Controllo TSA (Doppio)',
                                    ],
                                ],
                                'test2' => [
                                    'name' => 'Test B (Produttività, Metodo Qualitativo)',
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
                                    'name' => 'Test C (Controllo della contaminazione microbica)',
                                    'std_plates' => [
                                        28 => 'ID Piastra 1',
                                        29 => 'ID Piastra 2',
                                        30 => 'ID Piastra 3',
                                        31 => 'ID Piastra Controllo Bianco',
                                    ],
                                    'dbl_plates' => [
                                        32 => 'ID Piastra 1 (Doppio)',
                                        33 => 'ID Piastra 2 (Doppio)',
                                        34 => 'ID Piastra 3 (Doppio)',
                                        35 => 'ID Piastra Controllo Bianco (Doppio)',
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
                                        {{-- Special layout for Test B --}}
                                        @foreach($def['std_plates'] as $temp => $positions)
                                            <div class="col-md-6 mb-3">
                                                <div class="p-2 border rounded h-100">
                                                    <h6 class="text-muted">{{ $temp }}</h6>
                                                    @foreach($positions as $position => $indices)
                                                        <div class="row mb-2 align-items-center g-2">
                                                            <div class="col-sm-3"><label class="form-label mb-0">{{ $position }}</label></div>
                                                            @foreach($indices as $k => $i)
                                                                <div class="col-sm-4">
                                                                    <div class="input-group has-validation">
                                                                        <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                                        <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="P{{$k+1}}" value="{{ $plates[$i] ?? '' }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
                                        {{-- Generic layout for Test A and C --}}
                                        @foreach($def['std_plates'] as $i => $label)
                                            <div class="col-md-4 mb-2">
                                                <label for="plate_{{ $i }}" class="form-label">{{ $label }}</label>
                                                <div class="input-group has-validation">
                                                    <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="Solo numeri" value="{{ $plates[$i] ?? '' }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                    <div class="invalid-feedback">L'ID Piastra è obbligatorio.</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                {{-- Double Plates --}}
                                <div id="double-plates-group-{{ $test_id }}" class="d-none double-plates-container" data-test-id="{{ $test_id }}">
                                    <hr class="my-3">
                                    <h6 class="text-muted mb-3">Piastre per test in doppio</h6>
                                    <div class="row">                                        @if($test_id === 'test2')
                                            {{-- Special layout for Test B Double --}}
                                            @foreach($def['dbl_plates'] as $temp => $positions)
                                                <div class="col-md-6 mb-3">
                                                    <div class="p-2 border rounded h-100">
                                                        <h6 class="text-muted">{{ $temp }}</h6>
                                                        @foreach($positions as $position => $indices)
                                                            <div class="row mb-2 align-items-center g-2">
                                                                <div class="col-sm-3"><label class="form-label mb-0">{{ $position }}</label></div>
                                                                @foreach($indices as $k => $i)
                                                                    <div class="col-sm-4">
                                                                        <div class="input-group has-validation">
                                                                            <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="P{{$k+1}}" value="{{ $plates[$i] ?? '' }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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
                                            {{-- Generic layout for Test A and C Double --}}
                                            @foreach($def['dbl_plates'] as $i => $label)
                                            <div class="col-md-4 mb-2">
                                                <label for="plate_{{ $i }}" class="form-label">{{ $label }}</label>
                                                <div class="input-group has-validation">
                                                    <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control plate-input" id="plate_{{ $i }}" name="plates[{{ $i }}]" placeholder="Solo numeri" value="{{ $plates[$i] ?? '' }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                    <div class="invalid-feedback">L'ID Piastra è obbligatorio.</div>
                                                </div>
                                            </div>
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Salva Accettazione</button>
                    <a href="{{ route('acceptance.index') }}" class="btn btn-secondary btn-lg">Annulla</a>
                </form>
            </div>
        </div>
    </main>

    {{-- Script per attivare la validazione Bootstrap e la logica dei checkbox --}}
    <script>
        (function () {
          'use strict'
          var forms = document.querySelectorAll('.needs-validation')
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                let isValid = form.checkValidity();
                let customValidationPassed = true;

                const testCheckboxes = form.querySelectorAll('.test-checkbox');
                const noTestSelectedAlert = document.getElementById('no-test-selected-alert');
                let anyTestSelected = Array.from(testCheckboxes).some(cb => cb.checked);

                if (!anyTestSelected) {
                    noTestSelectedAlert.classList.remove('d-none');
                    customValidationPassed = false;
                } else {
                    noTestSelectedAlert.classList.add('d-none');
                }

                const plateInputs = form.querySelectorAll('input[name^="plates["]');
                
                plateInputs.forEach(input => {
                    input.setCustomValidity('');
                    input.classList.remove('is-invalid');
                });

                testCheckboxes.forEach((checkbox) => {
                    if (checkbox.checked) {
                        const testId = checkbox.value;
                        const doubleCheckbox = document.getElementById('double_' + testId);

                        const plateGroup = document.getElementById('plates-group-' + testId);
                        if (plateGroup) {
                            // Validate standard plates
                            const standardInputs = plateGroup.querySelectorAll('.standard-plates-row .plate-input');
                            standardInputs.forEach(plateInput => {
                                if (plateInput.value.trim() === '') {
                                    plateInput.setCustomValidity('Questo campo è obbligatorio per il test selezionato.');
                                    plateInput.classList.add('is-invalid');
                                    customValidationPassed = false;
                                }
                            });

                            // Validate double plates if checked
                            if (doubleCheckbox && doubleCheckbox.checked) {
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

            function updatePlateVisibility() {
                testCheckboxes.forEach((checkbox) => {
                    const testId = checkbox.value;
                    const doubleContainer = document.getElementById('double-' + testId + '-container');
                    const doubleCheckbox = document.getElementById('double_' + testId);
                    const plateGroup = document.getElementById('plates-group-' + testId);
                    const doublePlateGroup = document.getElementById('double-plates-group-' + testId);

                    if (checkbox.checked) {
                        if (doubleContainer) doubleContainer.style.display = 'inline-block';
                        if (plateGroup) plateGroup.classList.remove('d-none');

                        if (doubleCheckbox && doubleCheckbox.checked) {
                            if (doublePlateGroup) doublePlateGroup.classList.remove('d-none');
                        } else {
                            if (doublePlateGroup) doublePlateGroup.classList.add('d-none');
                        }
                    } else {
                        if (doubleContainer) doubleContainer.style.display = 'none';
                        if (plateGroup) plateGroup.classList.add('d-none');
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

            // Esegui al caricamento per mostrare i gruppi di piastre per i test già selezionati.
            updatePlateVisibility();
        });
    </script>

    <footer class="mt-auto text-center py-3 bg-light">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>
</body>
</html>