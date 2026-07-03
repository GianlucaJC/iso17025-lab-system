@component('mail::message')
# Notifica di Test Pronto per la Validazione

Gentile Responsabile di Laboratorio,

ti informiamo che un nuovo test è stato completato e firmato, ed è ora in attesa della tua validazione.

Ecco i dettagli:

- **Test Eseguito**: {{ $testType }}
- **Accettazione N°**: {{ $acceptance->acceptance_number }}
- **Lotto**: {{ $acceptance->lotto }}
- **Firmato da**: {{ $operatorName }}
- **Data Firma**: {{ $testResult->lab_signed_at->format('d/m/Y H:i') }}

Per favore, procedi con la revisione e la validazione del test cliccando sul pulsante qui sotto.

@component('mail::button', ['url' => $testUrl])
Visualizza e Valida il Test
@endcomponent

Grazie per la collaborazione
@endcomponent
