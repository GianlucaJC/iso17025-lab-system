<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Nuova Accettazione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">Pannello</a>
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
                <h3><i class="fas fa-clipboard-list me-2"></i>Nuova Scheda di Accettazione Campioni</h3>
            </div>
            <div class="card-body">
                {{-- Abilitiamo la validazione client-side di Bootstrap --}}
                <form method="POST" action="{{-- route('acceptance.store') --}}" class="needs-validation" novalidate>
                    @csrf

                    {{-- SEZIONE DATI GENERALI --}}
                    <h5>Dati Generali</h5>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="acceptance_number" class="form-label">Numero Accettazione</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                <input type="text" class="form-control" id="acceptance_number" name="acceptance_number" required>
                                <div class="invalid-feedback">Per favore, inserisci il numero di accettazione.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="lotto" class="form-label">Lotto</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                <input type="text" class="form-control" id="lotto" name="lotto" required>
                                <div class="invalid-feedback">Per favore, inserisci il lotto.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="sampling_date" class="form-label">Data Campionamento</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control" id="sampling_date" name="sampling_date" required>
                                <div class="invalid-feedback">Per favore, inserisci la data di campionamento.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="acceptance_date" class="form-label">Data Accettazione</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                <input type="date" class="form-control" id="acceptance_date" name="acceptance_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    {{-- SEZIONE IDENTIFICAZIONE PIASTRE --}}
                    <h5>Identificazione Piastre</h5>
                    <div class="row mb-4">
                        @for ($i = 1; $i <= 15; $i++)
                            <div class="col-md-4 mb-2">
                                <label for="plate_{{ $i }}" class="form-label">ID Piastra {{ $i }}</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text"><i class="far fa-dot-circle"></i></span>
                                    {{-- L'input è numerico, obbligatorio e filtra i caratteri non numerici in tempo reale --}}
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        class="form-control"
                                        id="plate_{{ $i }}"
                                        name="plates[]"
                                        placeholder="Solo numeri"
                                        {{-- 'required' verrà gestito dinamicamente via JS e validazione server-side --}}
                                        data-test-group="{{ ceil($i / 5) }}" {{-- Group 1 for plates 1-5, Group 2 for 6-10, Group 3 for 11-15 --}}
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    <div class="invalid-feedback">ID Piastra {{ $i }} è obbligatorio.</div>
                                </div>
                            </div>
                        @endfor
                    </div>

                    {{-- SEZIONE TEST DA ESEGUIRE --}}
                    <h5>Test da Eseguire</h5>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test1" id="test1" name="tests[]">
                                <label class="form-check-label" for="test1">
                                    Test A (Controllo del pH)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test2" id="test2" name="tests[]">
                                <label class="form-check-label" for="test2">
                                    Test B (Produttività, Metodo Qualitativo)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input test-checkbox" type="checkbox" value="test3" id="test3" name="tests[]">
                                <label class="form-check-label" for="test3">
                                    Test C (Controllo della contaminazione microbica)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none" id="no-test-selected-alert">
                        Seleziona almeno un test per procedere.
                    </div>

                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>Salva Accettazione</button>
                </form>
            </div>
        </div>
    </main>

    {{-- Script per attivare la validazione Bootstrap --}}
    <script>
        // Esempio di JavaScript di base per disabilitare l'invio di moduli se ci sono campi non validi
        (function () {
          'use strict'
        
          // Recupera tutti i moduli a cui vogliamo applicare stili di convalida Bootstrap personalizzati
          var forms = document.querySelectorAll('.needs-validation')
        
          // Scansiona i moduli e impedisci l'invio
          Array.prototype.slice.call(forms)
            .forEach(function (form) {
              form.addEventListener('submit', function (event) {
                let isValid = form.checkValidity(); // Validazione HTML5 standard
                let customValidationPassed = true; // Flag per la nostra validazione personalizzata

                // 1. Validazione: Almeno un test deve essere selezionato
                const testCheckboxes = form.querySelectorAll('.test-checkbox');
                const noTestSelectedAlert = document.getElementById('no-test-selected-alert');
                let anyTestSelected = Array.from(testCheckboxes).some(cb => cb.checked);

                if (!anyTestSelected) {
                    noTestSelectedAlert.classList.remove('d-none');
                    customValidationPassed = false;
                } else {
                    noTestSelectedAlert.classList.add('d-none');
                }

                // 2. Validazione: ID Piastre basati sui test selezionati
                const plateInputs = form.querySelectorAll('input[name="plates[]"]');
                
                // Resetta lo stato di validazione delle piastre prima di ricontrollare
                plateInputs.forEach(input => {
                    input.setCustomValidity(''); // Rimuove messaggi di errore custom
                    input.classList.remove('is-invalid'); // Rimuove lo stile di errore
                });

                testCheckboxes.forEach((checkbox, index) => {
                    if (checkbox.checked) {
                        // Mappa l'indice del checkbox al gruppo di piastre (0-4 per test1, 5-9 per test2, 10-14 per test3)
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

                // Se la validazione HTML5 o la nostra validazione custom falliscono, impediamo l'invio
                if (!isValid || !customValidationPassed) {
                  event.preventDefault()
                  event.stopPropagation()
                }
        
                form.classList.add('was-validated')
              }, false)
            })

            // Aggiungi event listeners ai checkbox dei test per aggiornare la validazione delle piastre
            const testCheckboxes = form.querySelectorAll('.test-checkbox');
            testCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Quando un test cambia, forziamo una ri-validazione del form
                    // per aggiornare lo stato visivo degli input delle piastre.
                    // Questo è un modo semplice per riattivare la logica di validazione.
                    if (form.classList.contains('was-validated')) {
                        // Simula un submit per far rieseguire la validazione senza inviare il form
                        const tempSubmit = document.createElement('button');
                        tempSubmit.style.display = 'none';
                        form.appendChild(tempSubmit);
                        tempSubmit.click();
                        form.removeChild(tempSubmit);
                    }
                });
            });
        })()
    </script>

    <footer class="mt-auto text-center py-3 bg-light">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>
</body>
</html>