<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <title>Logs</title>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-clock-history text-primary"></i> Journal des Activités</h2>
        <div class="table-responsive shadow rounded">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th><i class="bi bi-calendar-date"></i> Date</th>
                        <th><i class="bi bi-person-circle"></i> Utilisateur</th>
                        <th><i class="bi bi-pencil-square"></i> Event</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($log->causer)
                                    <i class="bi bi-person-fill text-success"></i>
                                    {{ $log->causer->first_name }} {{ $log->causer->last_name }}
                                @else
                                    <i class="bi bi-robot text-muted"></i> Système
                                @endif
                            </td>
                            <td><span class="badge bg-primary">{{ ucfirst($log->description) }}</span></td>
                            
                            <td>
                                
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Aucune activité enregistrée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    
</body>
</html>
