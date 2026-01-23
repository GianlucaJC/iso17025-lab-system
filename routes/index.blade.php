<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Gestione Strumenti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <div>
                <a class="nav-link d-inline-block align-middle" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
                @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                    <a class="nav-link d-inline-block align-middle ms-3 fw-bold text-primary" href="{{ route('instruments.index') }}">Gestione Strumenti</a>
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
                <h3><i class="fas fa-tools me-2"></i>Gestione Tabelle di Servizio - Strumenti</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Tabella Tipi di Strumento --}}
                    <div class="col-md-5">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Tipi di Strumento</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($instruments as $instrument)
                                        <tr>
                                            <td>{{ $instrument->id }}</td>
                                            <td>{{ $instrument->name }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Tabella Anagrafica Strumenti --}}
                    <div class="col-md-7">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>Anagrafica Strumenti</h5>
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createItemModal">
                                    <i class="fas fa-plus me-1"></i> Aggiungi Strumento
                                </button>
                            </div>
                            <div class="card-body">
                                <table id="instrumentItemsTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Identificativo</th>
                                            <th>Tipo</th>
                                            <th>Descrizione</th>
                                            <th>Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($instrumentItems as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->identifier }}</td>
                                            <td><span class="badge bg-info text-dark">{{ $item->instrument->name }}</span></td>
                                            <td>{{ $item->description }}</td>
                                            <td>
                                                <button class="btn btn-primary btn-sm edit-item-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editItemModal"
                                                        data-id="{{ $item->id }}"
                                                        data-instrument-id="{{ $item->instrument_id }}"
                                                        data-identifier="{{ $item->identifier }}"
                                                        data-description="{{ $item->description }}"
                                                        data-action="{{ route('instruments.updateItem', $item->id) }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('instruments.destroyItem', $item->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Create Item Modal -->
    <div class="modal fade" id="createItemModal" tabindex="-1" aria-labelledby="createItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('instruments.storeItem') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createItemModalLabel">Aggiungi Nuovo Strumento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="create_instrument_id" class="form-label">Tipo Strumento</label>
                            <select class="form-select" id="create_instrument_id" name="instrument_id" required>
                                <option value="" disabled selected>Seleziona un tipo...</option>
                                @foreach($instruments as $instrument)
                                    <option value="{{ $instrument->id }}">{{ $instrument->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="create_identifier" class="form-label">Identificativo (Univoco)</label>
                            <input type="text" class="form-control" id="create_identifier" name="identifier" required>
                        </div>
                        <div class="mb-3">
                            <label for="create_description" class="form-label">Descrizione</label>
                            <input type="text" class="form-control" id="create_description" name="description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editItemForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editItemModalLabel">Modifica Strumento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_instrument_id" class="form-label">Tipo Strumento</label>
                            <select class="form-select" id="edit_instrument_id" name="instrument_id" required>
                                @foreach($instruments as $instrument)
                                    <option value="{{ $instrument->id }}">{{ $instrument->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_identifier" class="form-label">Identificativo (Univoco)</label>
                            <input type="text" class="form-control" id="edit_identifier" name="identifier" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descrizione</label>
                            <input type="text" class="form-control" id="edit_description" name="description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="mt-auto text-center py-3 bg-white">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#instrumentItemsTable').DataTable({
                language: {
                    "sEmptyTable": "Nessun dato presente nella tabella", "sInfo": "Vista da _START_ a _END_ di _TOTAL_ elementi", "sInfoEmpty": "Vista da 0 a 0 di 0 elementi", "sInfoFiltered": "(filtrati da _MAX_ elementi totali)", "sInfoPostFix": "", "sInfoThousands": ".", "sLengthMenu": "Visualizza _MENU_ elementi", "sLoadingRecords": "Caricamento...", "sProcessing": "Elaborazione...", "sSearch": "Cerca:", "sZeroRecords": "La ricerca non ha portato alcun risultato.", "oPaginate": { "sFirst": "Inizio", "sPrevious": "Precedente", "sNext": "Successivo", "sLast": "Fine" }, "oAria": { "sSortAscending": ": attiva per ordinare la colonna in ordine crescente", "sSortDescending": ": attiva per ordinare la colonna in ordine decrescente" }
                }
            });

            // Popola il modale di modifica
            $('.edit-item-btn').on('click', function() {
                const action = $(this).data('action');
                const instrumentId = $(this).data('instrument-id');
                const identifier = $(this).data('identifier');
                const description = $(this).data('description');

                $('#editItemForm').attr('action', action);
                $('#edit_instrument_id').val(instrumentId);
                $('#edit_identifier').val(identifier);
                $('#edit_description').val(description);
            });

            // Conferma per l'eliminazione
            $('.delete-form').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                Swal.fire({
                    title: 'Sei sicuro?',
                    text: "Questa azione non può essere annullata!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, elimina!',
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