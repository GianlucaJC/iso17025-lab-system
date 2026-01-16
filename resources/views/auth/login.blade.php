<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>Login</title>
    <!-- Bootstrap CSS per uno stile semplice e pulito -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa; /* Sfondo grigio chiaro */
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #0d6efd; /* Colore primario di Bootstrap */
        }
        .login-header h4 {
            margin-top: 1rem;
            color: #343a40;
        }
    </style>
</head>
<body class="min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5"> <!-- Colonna leggermente più stretta per un look più compatto -->
                <div class="login-header">
                    <i class="fas fa-user-circle"></i> <!-- Icona grafica -->
                    <h4>Accedi al Sistema ISO 17025</h4>
                </div>
                <div class="card">
                    <div class="card-body p-4"> <!-- Aggiunto padding al corpo della card -->
                        <!-- Se ci sono errori (es. credenziali errate), li mostriamo qui -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        {{-- Mostriamo solo la parte "umana" del messaggio di errore, nascondendo il debug tecnico --}}
                                        <li>{{ \Illuminate\Support\Str::before($error, ' [Debug Info:') }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-4"> <!-- Aggiunto margine superiore per il bottone -->
                                <button type="submit" class="btn btn-primary btn-lg"> <!-- Bottone più grande -->
                                    <i class="fas fa-sign-in-alt me-2"></i> Accedi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>