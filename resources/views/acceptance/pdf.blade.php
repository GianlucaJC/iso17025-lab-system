<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Rapporto di Prova {{ $acceptance->acceptance_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
        @page { margin: 100px 40px 80px 40px; }
        header { position: fixed; top: -80px; left: 0px; right: 0px; height: 70px; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; text-align: center; font-size: 8px; border-top: 1px solid #ccc; padding-top: 5px; }
        .page-number:after { content: "Pagina " counter(page) " di " counter(pages); }
        h1, h2, h3 { font-weight: normal; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 20px; text-decoration: underline; }
        h2 { font-size: 12px; border-bottom: 1px solid #555; padding-bottom: 5px; margin-top: 20px; margin-bottom: 10px; }
        h3 { font-size: 11px; font-weight: bold; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .info-table { border: none; }
        .info-table strong { font-weight: bold; }
        .info-table td { border: none; padding: 2px 0; }
        .signatures { margin-top: 40px; border: none; }
        .signatures td { border: none; padding: 10px 0; }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.1); /* Light grey, semi-transparent */
            z-index: -1000; /* Behind content */
            white-space: nowrap;
        }
    </style>
</head>
<body>
    @php
        $approvalDate = null;
        $reportDate = $report_date; // Mantiene la data originale come fallback

        if ($isPdfComplete) {
            $validationDates = [];
            // Raccoglie tutte le date di validazione disponibili per i test richiesti
            if (in_array('test1', $acceptance->tests) && $testAResult && $testAResult->rl_signed_at) {
                $validationDates[] = $testAResult->rl_signed_at;
            }
            if (in_array('test2', $acceptance->tests) && $testBResult && $testBResult->rl_signed_at) {
                $validationDates[] = $testBResult->rl_signed_at;
            }
            if (in_array('test3', $acceptance->tests) && $testCResult && $testCResult->rl_signed_at) {
                $validationDates[] = $testCResult->rl_signed_at;
            }

            if (!empty($validationDates)) {
                // Trova la data più recente e la usa sia per l'approvazione che per il report
                $latestDate = max($validationDates);
                $approvalDate = $latestDate->format('d.m.Y');
                $reportDate = $approvalDate;
            }
        }
    @endphp
    <header>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 70%; border: none;">
                    <strong>N. RAPPORTO DI PROVA:</strong> {{ $acceptance->acceptance_number }}_0<br>
                    <strong>Data Rapporto di Prova:</strong> {{ $reportDate }}
                </td>
                <td style="width: 30%; text-align: right; border: none;" class="page-number">
                    {{-- Il numero di pagina viene inserito da dompdf --}}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        Liofilchem® s.r.l. Via Uruguay 64026, Roseto degli Abruzzi (TE) Italia Tel +39 0858930745 - Fax +39 0858930330 - Accredia n. 02165.
    </footer>

    @if(!$isPdfComplete)
        <div class="watermark">ANTEPRIMA NON VALIDATA</div>
    @endif

    <main>
        <h1>RAPPORTO DI PROVA</h1>

        <h2>Dati Generali</h2>
        <table class="info-table">
            <tr>
                <td style="width: 25%;"><strong>N. Accettazione:</strong></td>
                <td style="width: 75%;">{{ $acceptance->acceptance_number }}</td>
            </tr>
            <tr>
                <td><strong>Data di campionamento:</strong></td>
                <td>{{ \Carbon\Carbon::parse($acceptance->sampling_date)->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td><strong>Campionato da:</strong></td>
                <td>Operatore di Produzione</td>
            </tr>
            <tr>
                <td><strong>Data accettazione:</strong></td>
                <td>{{ \Carbon\Carbon::parse($acceptance->acceptance_date)->format('d.m.Y') }}</td>
            </tr>
        </table>

        <h2>Informazioni Prodotto</h2>
        <table class="info-table">
            <tr>
                <td style="width: 25%;"><strong>Prodotto:</strong></td>
                <td style="width: 75%;">{{ $productInfo['name'] }}</td>
            </tr>
            <tr>
                <td><strong>Codice:</strong></td>
                <td>{{ $productInfo['code'] }}</td>
            </tr>
            <tr>
                <td><strong>Lotto:</strong></td>
                <td>{{ $acceptance->lotto }}</td>
            </tr>
            <tr>
                <td><strong>Data di scadenza:</strong></td>
                <td>{{ \Carbon\Carbon::parse($productInfo['expiry_date'])->format('d.m.Y') }}</td>
            </tr>
        </table>

        <h2>Analisi Effettuate</h2>

        {{-- 1. Controllo del pH (Test A) --}}
        @if($testAResult)
            <h3>1. Controllo del pH</h3>
            <table class="info-table">
                <tr><td style="width: 25%;"><strong>Metodo di Prova:</strong></td><td style="width: 75%;">MA09 Rev.5 del 20.10.2023</td></tr>
                <tr><td><strong>ID Campione:</strong></td><td>{{ $acceptance->plates[0] ?? 'N/D' }}</td></tr>
            </table>
            <table>
                <thead><tr><th>Parametro</th><th>Specifiche</th><th>Risultato e Incertezza</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Valore di pH (25°C)</td>
                        <td>7.4 ± 0.2</td>
                        <td>{{ number_format($testAResult->ph_value, 2) }} ± INC</td> {{-- 'INC' è un placeholder --}}
                    </tr>
                </tbody>
            </table>
            <table class="info-table" style="margin-top: 5px;">
                <tr>
                    <td style="width: 25%;"><strong>Data Inizio Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testAResult->test_date)->format('d.m.Y') }}</td>
                    <td style="width: 25%;"><strong>Data Fine Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testAResult->test_date)->format('d.m.Y') }}</td>
                </tr>
            </table>
        @endif

        {{-- 2. Produttività, Metodo Qualitativo (Test C) --}}
        @if($testCResult)
            <h3>2. Produttività, Metodo Qualitativo</h3>
            <table class="info-table">
                <tr><td style="width: 25%;"><strong>Metodo di Prova:</strong></td><td style="width: 75%;">MA60 Rev.4 del 07.03.2024</td></tr>
                <tr><td><strong>ID Campione:</strong></td><td>{{ $testCResult->plate_id_start_lotto ?? 'N/D' }}, {{ $testCResult->plate_id_mid_lotto ?? 'N/D' }}, {{ $testCResult->plate_id_end_lotto ?? 'N/D' }}</td></tr>
                <tr><td><strong>Inoculo:</strong></td><td>&#8804; 100 CFU</td></tr>
            </table>
            <table>
                <thead><tr><th>Ceppo di controllo</th><th>Incubazione</th><th>Specifiche</th><th>Risultato</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Salmonella typhimurium ATCC 14028</td>
                        <td>24 ± 3h a 35 ± 2°C</td>
                        <td>Colonie rosse con centro nero</td>
                        <td>{{ $testCResult->tsa_growth_result == 'rilevata' ? 'Rilevata' : 'Non Rilevata' }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="info-table" style="margin-top: 5px;">
                <tr>
                    <td style="width: 25%;"><strong>Data Inizio Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testCResult->test_start_datetime)->format('d.m.Y') }}</td>
                    <td style="width: 25%;"><strong>Data Fine Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testCResult->test_end_datetime)->format('d.m.Y') }}</td>
                </tr>
            </table>
        @endif

        {{-- 3. Controllo della contaminazione microbica (Test B) --}}
        @if($testBResult)
            <h3>3. Controllo della contaminazione microbica</h3>
            @php
                $testBPlates = array_filter([
                    $testBResult->plate_id_start_plate1_35_run1, $testBResult->plate_id_start_plate2_35_run1,
                    $testBResult->plate_id_mid_plate1_35_run1,   $testBResult->plate_id_mid_plate2_35_run1,
                    $testBResult->plate_id_end_plate1_35_run1,   $testBResult->plate_id_end_plate2_35_run1,
                    $testBResult->plate_id_start_plate1_22_run1, $testBResult->plate_id_start_plate2_22_run1,
                    $testBResult->plate_id_mid_plate1_22_run1,   $testBResult->plate_id_mid_plate2_22_run1,
                    $testBResult->plate_id_end_plate1_22_run1,   $testBResult->plate_id_end_plate2_22_run1,
                ]);
            @endphp
            <table class="info-table">
                <tr><td style="width: 25%;"><strong>Metodo di Prova:</strong></td><td style="width: 75%;">MA61 Rev.2 del 07.03.2024</td></tr>
                <tr><td><strong>ID Campione:</strong></td><td>{{ implode(' – ', $testBPlates) }}</td></tr>
            </table>
            <table>
                <thead><tr><th>Incubazione</th><th>Specifiche</th><th>Risultato</th></tr></thead>
                <tbody>
                    @php
                        // Logica per determinare il risultato aggregato per 35C
                        $growth_35 = 'Nessuna crescita';
                        if (
                            $testBResult->growth_result_35_start_plate1_run1 == 'rilevata' || $testBResult->growth_result_35_start_plate2_run1 == 'rilevata' ||
                            $testBResult->growth_result_35_mid_plate1_run1 == 'rilevata'   || $testBResult->growth_result_35_mid_plate2_run1 == 'rilevata'   ||
                            $testBResult->growth_result_35_end_plate1_run1 == 'rilevata'   || $testBResult->growth_result_35_end_plate2_run1 == 'rilevata'
                        ) { $growth_35 = 'Crescita rilevata'; }

                        // Logica per determinare il risultato aggregato per 22C
                        $growth_22 = 'Nessuna crescita';
                        if (
                            $testBResult->growth_result_22_start_plate1_run1 == 'rilevata' || $testBResult->growth_result_22_start_plate2_run1 == 'rilevata' ||
                            $testBResult->growth_result_22_mid_plate1_run1 == 'rilevata'   || $testBResult->growth_result_22_mid_plate2_run1 == 'rilevata'   ||
                            $testBResult->growth_result_22_end_plate1_run1 == 'rilevata'   || $testBResult->growth_result_22_end_plate2_run1 == 'rilevata'
                        ) { $growth_22 = 'Crescita rilevata'; }
                    @endphp
                    <tr>
                        <td>35 ± 2°C, 7 giorni</td>
                        <td>Nessuna crescita</td>
                        <td>{{ $growth_35 }}</td>
                    </tr>
                    <tr>
                        <td>22,5 ± 2°C, 7 giorni</td>
                        <td>Nessuna crescita</td>
                        <td>{{ $growth_22 }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="info-table" style="margin-top: 5px;">
                <tr>
                    <td style="width: 25%;"><strong>Data Inizio Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testBResult->test_start_datetime)->format('d.m.Y') }}</td>
                    <td style="width: 25%;"><strong>Data Fine Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testBResult->test_end_datetime)->format('d.m.Y') }}</td>
                </tr>
            </table>
        @endif

        <table class="signatures">
            <tr>
                <td style="width: 50%;">
                    <strong>Approvato il:</strong> {{ $approvalDate ?? '________________' }}
                </td>
                <td style="width: 50%;">
                    @if($isPdfComplete)
                        <strong>Firma:</strong> Documento firmato elettronicamente
                    @else
                        <strong>Firma:</strong> _________________________
                    @endif
                </td>
            </tr>
             <tr>
                <td>
                    <strong>Responsabile di Laboratorio:</strong>
                    {{ $approvalDate ? 'Dott. F. D’Emidio' : '_________________________' }}
                </td>
                <td>
                    {{-- Spazio per la firma autografa --}}
                </td>
            </tr>
        </table>

    </main>
</body>
</html>