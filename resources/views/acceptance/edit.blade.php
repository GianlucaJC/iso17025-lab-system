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

                    {{-- SEZIONE IDENTIFICAZIONE PIASTRE --}}
                    <h5>Identificazione Piastre</h5>
                    <div id="plates-section">
                        @php
                            $plates = old('plates', $acceptance->plates ?? []);
                            $test_groups = ['Test A (Piastre 1-5)', 'Test B (Piastre 6-10)', 'Test C (Piastre 11-15)'];
                        @endphp
                        @for ($g = 0; $g < 3; $g++)
                            <div id="plates-group-{{ $g + 1 }}" class="row mb-3 d-none">
                                <h6>{{ $test_groups[$g] }}</h6>
                                @for ($i = ($g * 5) + 1; $i <= ($g + 1) * 5; $i++)
                                    <div class="col-md-4 mb-2">
                                        <label for="plate_{{ $i }}" class="form-label">ID Piastra {{ $i }}</label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text p-1"><img src="{{ asset('images/piastra-icona.png') }}" alt="Icona Piastra" style="height: 20px; width: auto;"></span>
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                class="form-control"
                                                id="plate_{{ $i }}"
                                                name="plates[{{ $i - 1 }}]"
                                                placeholder="Solo numeri"
                                                value="{{ $plates[$i - 1] ?? '' }}"
                                                data-test-group="{{ $g + 1 }}"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                {{ $is_readonly ? 'disabled' : '' }}>
                                            <div class="invalid-feedback">ID Piastra {{ $i }} è obbligatorio.</div>
                                        </div>
                                    </div>
                                @endfor
                                </div>
                        @endfor
                    </div>

                    {{-- SEZIONE TEST DA ESEGUIRE --}}
                    <h5>Test da Eseguire</h5>
                    <div class="row mb-4">
                        @php
                            $selectedTests = old('tests', $acceptance->tests ?? []);
                        @endphp
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test1" id="test1" name="tests[]" @if(in_array('test1', $selectedTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label" for="test1">
                                    Test A (Controllo del pH)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test2" id="test2" name="tests[]" @if(in_array('test2', $selectedTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label" for="test2">
                                    Test B (Produttività, Metodo Qualitativo)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test3" id="test3" name="tests[]" @if(in_array('test3', $selectedTests)) checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                <label class="form-check-label" for="test3">
                                    Test C (Controllo della contaminazione microbica)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none" id="no-test-selected-alert">
                        Seleziona almeno un test per procedere.
                    </div>

                    @if(!$is_readonly)
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Salva Modifiche</button>
                    @endif
                    <a href="{{ route('acceptance.index') }}" class="btn btn-secondary btn-lg">
                        @if($is_readonly)
                            <i class="fas fa-arrow-left me-2"></i>Torna all'elenco
                        @else
                            Annulla
                        @endif
                    </a>
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

                const plateInputs = form.querySelectorAll('input[name="plates[]"]');
                
                plateInputs.forEach(input => {
                    input.setCustomValidity('');
                    input.classList.remove('is-invalid');
                });

                testCheckboxes.forEach((checkbox, index) => {
                    if (checkbox.checked) {
                        const groupIndexStart = index * 5;
                        const groupIndexEnd = groupIndexStart + 5;

                        for (let i = groupIndexStart; i < groupIndexEnd; i++) {
                            const plateInput = plateInputs[i];
                            if (plateInput && plateInput.value.trim() === '') {
                                plateInput.setCustomValidity('Questo campo è obbligatorio per il test selezionato.');
                                plateInput.classList.add('is-invalid');
                                customValidationPassed = false;
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

            function updatePlateVisibility() {
                testCheckboxes.forEach((checkbox, index) => {
                    const groupNumber = index + 1;
                    const plateGroup = document.getElementById('plates-group-' + groupNumber);
                    if (plateGroup) {
                        if (checkbox.checked) {
                            plateGroup.classList.remove('d-none');
                        } else {
                            plateGroup.classList.add('d-none');
                        }
                    }
                });
            }

            testCheckboxes.forEach(checkbox => {
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
