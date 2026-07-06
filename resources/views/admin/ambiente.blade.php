<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Ambiente lavoro</title>
</head>
<body>
<div class="container mt-4">
    <h1>Ambiente lavoro</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.ambiente.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nome DB (DB_DATABASE)</label>
            <input type="text" name="db_name" class="form-control" value="{{ old('db_name', $db) }}">
        </div>
        <button class="btn btn-primary">Salva</button>
        <a href="{{ route('acceptance.index') }}" class="btn btn-secondary ms-2">Torna indietro</a>
    </form>

    <div class="mt-3">
        <p>Dopo la modifica potrebbe essere necessario riavviare i servizi o eseguire `php artisan config:clear`.</p>
    </div>
</div>
</body>
</html>
