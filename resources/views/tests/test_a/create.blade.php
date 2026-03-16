<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        $is_edit = isset($test_a_result);
        $is_readonly = $is_readonly ?? false;
        $form_title = $is_edit ? ($is_readonly ? 'Visualizza Risultati' : 'Modifica Risultati') : 'Esecuzione';
    @endphp
    <title>{{ $form_title }} Test A - MA_09_Misurazione del pH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <div>
                @if(!($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4))
                    <a href="{{ route('acceptance.create') }}" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i>Nuova Accettazione</a>
                @endif
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
                @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('instruments.index') }}">Gestione Strumenti</a>
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('methods.index') }}">Gestione revisioni</a>
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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card position-relative">
            <div class="card-header {{ $is_edit && !$is_readonly ? 'bg-warning' : 'bg-primary text-white' }}">
                <h3>
                    @if($is_edit)
                        @if($is_readonly) <i class="fas fa-eye me-2"></i>Visualizza @else <i class="fas fa-edit me-2"></i>Modifica @endif
                    @else
                        <i class="fas fa-vial me-2"></i>Esecuzione
                    @endif
                    Test A: MA_09_Misurazione del pH
                </h3>
            </div>
            <div class="card-body p-4">
                {{-- INIZIO: Blocco Operatore Accettazione --}}
                @if(isset($usersMap) && isset($acceptance))
                <div class="position-absolute top-0 end-0 p-3" style="z-index: 10;">
                    <div class="card bg-light shadow-sm">
                        <div class="card-body p-2">
                            <h6 class="card-title mb-1 text-muted small">Op. Accettazione</h6>
                            <p class="card-text mb-0 fw-bold">
                                <i class="fas fa-user-check me-1 text-primary"></i>
                                {{ $usersMap[$acceptance->user_id]['operatore'] ?? 'N/D' }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif
                {{-- FINE: Blocco Operatore Accettazione --}}
                <form method="POST" action="{{ $is_edit ? route('test-a.update', $test_a_result->id) : route('test-a.store', $acceptance->id) }}" class="needs-validation" novalidate>
                    @csrf
                    @if($is_edit) @method('PUT') @endif

                    {{-- Dati di Riferimento --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati di Riferimento</legend>
                        <div class="row p-3 bg-light border rounded">
                            <div class="col-md-6"><label class="form-label fw-bold">Lotto</label><p class="form-control-plaintext">{{ $acceptance->lotto }}</p></div>
                            <div class="col-md-6"><label class="form-label fw-bold">N. Accettazione</label><p class="form-control-plaintext">{{ $acceptance->acceptance_number }}</p></div>
                        </div>
                    </fieldset>

                    {{-- Dati Prova --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati Prova</legend>
                        <div class="row g-3">
                            <div class="col-md-4 initial-data-section">
                                <label for="test_date" class="form-label">Data Prova</label>
                                <input type="date" class="form-control" id="test_date" name="test_date" value="{{ old('test_date', $is_edit ? \Carbon\Carbon::parse($test_a_result->test_date)->format('Y-m-d') : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                            </div>

              

                            <div class="col-md-4">
                                <label for="ph_meter" class="form-label">ID pH-metro</label>
                                <select class="form-select @error('ph_meter') is-invalid @enderror" id="ph_meter" name="ph_meter" {{ $is_readonly ? 'disabled' : '' }} required>
                                    <option value="">Seleziona pH-metro...</option>
                                    @foreach($ph_meters as $meter)
                                        @php
                                            $selectedValue = old('ph_meter', $is_edit ? $test_a_result->ph_meter : '');
                                        @endphp
                                        <option value="{{ $meter->identifier }}" {{ $selectedValue == $meter->identifier ? 'selected' : '' }}>
                                            {{ $meter->identifier }} {{ $meter->description ? '('.$meter->description.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    @error('ph_meter') {{ $message }} @else Selezionare un pH-metro. @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="ph_probe" class="form-label">Sonda pH</label>
                                <select class="form-select @error('ph_probe') is-invalid @enderror" id="ph_probe" name="ph_probe" {{ $is_readonly ? 'disabled' : '' }} required>
                                    <option value="">Seleziona sonda...</option>
                                    @foreach($ph_probes as $probe)
                                        @php
                                            $selectedValue = old('ph_probe', $is_edit ? $test_a_result->ph_probe : '');
                                        @endphp
                                        <option value="{{ $probe->identifier }}" {{ $selectedValue == $probe->identifier ? 'selected' : '' }}>
                                            {{ $probe->identifier }} {{ $probe->description ? '('.$probe->description.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    @error('ph_probe') {{ $message }} @else Selezionare una sonda. @enderror
                                </div>
                            </div>
                            <div class="col-md-4"> {{-- Modificato da col-12 a col-md-4 per limitare la larghezza --}}
                                <label for="ph_value_slider" class="form-label">Valore pH (Specifiche: 7.4 ± 0.2)</label>
                                <input type="number" class="form-control" id="ph_value" name="ph_value" min="6.0" max="9.0" step="0.01" value="{{ old('ph_value', $is_edit ? $test_a_result->ph_value : '') }}" required {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                            {{-- Il campo di input numerico per il pH ora occupa 4 colonne su schermi medi e grandi. --}}

                            {{-- La barra del pH (slider) è stata rimossa per uniformità con le altre schermate. --}}
                            {{-- Il campo di input numerico per il pH rimane. --}}
                            {{-- L'indicatore di conformità del pH è stato rimosso insieme allo slider. --}}
                        </div>
                    </fieldset>

                    {{-- Esito --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Esito</legend>
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <input type="radio" class="btn-check" name="outcome" id="outcome_idoneo" value="idoneo" autocomplete="off" {{ old('outcome', $is_edit ? $test_a_result->outcome : '') == 'idoneo' ? 'checked' : '' }} required {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="btn btn-outline-success btn-lg" for="outcome_idoneo"><i class="fas fa-check-circle me-2"></i>Idoneo</label>
                                    
                                    <input type="radio" class="btn-check" name="outcome" id="outcome_non_idoneo" value="non_idoneo" autocomplete="off" {{ old('outcome', $is_edit ? $test_a_result->outcome : '') == 'non_idoneo' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="btn btn-outline-danger btn-lg" for="outcome_non_idoneo"><i class="fas fa-times-circle me-2"></i>Non Idoneo</label>
                                </div>
                            </div>
                            <div class="col-md-6" id="non-compliance-section" style="display: none;">
                                <label for="non_compliance_ref" class="form-label">Riferimento Non Conformità</label>
                                <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" value="{{ old('non_compliance_ref', $is_edit ? $test_a_result->non_compliance_ref : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Motivazione Modifica --}}
                    @if ($is_edit && !$is_readonly)
                    <fieldset class="mb-4">
                        <legend class="h5">Motivazione della Modifica</legend>
                        <div class="form-group">
                            <label for="modification_reason" class="form-label">La modifica dei risultati richiede una motivazione (min. 10 caratteri).</label>
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
                            @if($is_readonly) <i class="fas fa-arrow-left me-2"></i>Torna indietro @else <i class="fas fa-times me-2"></i>Annulla @endif
                        </a>
                        @if(!$is_readonly)
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>{{ $is_edit ? 'Salva Modifiche' : 'Salva Risultati' }}
                            </button>
                        @endif
                    </div>
                </form>

                {{-- Sezione Firme e Validazione --}}
                @if($is_edit)
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Operatore</h6>
                            <p>{{ $usersMap[$test_a_result->operator_id]['operatore'] ?? 'N/D' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Firma Tecnico di Laboratorio</h6>
                            @if($test_a_result->lab_signed_at)
                                <p class="text-success">Firmato da <strong>{{ $usersMap[$test_a_result->lab_signature_id]['operatore'] ?? 'N/D' }}</strong><br>il {{ $test_a_result->lab_signed_at->format('d/m/Y H:i') }}</p>
                            @else
                                <p class="text-muted">Non ancora firmato</p>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h6>Validazione Responsabile Laboratorio</h6>
                            @if($test_a_result->rl_signed_at)
                                <p class="text-primary">Validato da <strong>{{ $usersMap[$test_a_result->rl_signature_id]['operatore'] ?? 'N/D' }}</strong><br>il {{ $test_a_result->rl_signed_at->format('d/m/Y H:i') }}</p>
                            @else
                                <p class="text-muted">Non ancora validato</p>
                                @if($test_a_result->lab_signed_at && !$test_a_result->rl_signed_at && isset($currentUser['user17025']) && $currentUser['user17025'] == 4)
                                    <form action="{{ route('test-a.validate', $test_a_result->id) }}" method="POST" class="validate-form">
                                        @csrf
                                        <input type="hidden" name="source" value="run_test">
                                        <button type="submit" class="btn btn-primary">Valida Test</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 bg-white">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione validazione Bootstrap
            var form = document.querySelector('.needs-validation');
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            }

            // Gestione visibilità campo Non Conformità
            const outcomeRadios = document.querySelectorAll('input[name="outcome"]');
            const nonComplianceSection = document.getElementById('non-compliance-section');
            const nonComplianceInput = document.getElementById('non_compliance_ref');

            function toggleNonCompliance() {
                const isNonIdoneo = document.getElementById('outcome_non_idoneo').checked;
                if (isNonIdoneo) {
                    nonComplianceSection.style.display = 'block';
                    nonComplianceInput.required = true;
                } else {
                    nonComplianceSection.style.display = 'none';
                    nonComplianceInput.required = false;
                }
            }

            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonCompliance));
            toggleNonCompliance(); // Esegui al caricamento

            // Gestione conferma validazione con SweetAlert2
            $('form.validate-form').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                Swal.fire({
                    title: 'Sei sicuro di voler validare questo test?',
                    text: "L'azione è definitiva e renderà il test immutabile.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, valida!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>