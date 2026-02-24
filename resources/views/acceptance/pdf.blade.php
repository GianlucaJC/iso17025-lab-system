<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Rapporto di Prova {{ $acceptance->acceptance_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
        @page { margin: 100px 40px 70px 40px; }
        header { position: fixed; top: -90px; left: 0px; right: 0px; height: 60px; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; text-align: center; font-size: 8px; border-top: 1px solid #ccc; padding-top: 5px; }
        h1, h2, h3 { font-weight: normal; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 20px; text-decoration: underline; }
        h2 { font-size: 12px; border-bottom: 1px solid #555; padding-bottom: 3px; margin-top: 15px; margin-bottom: 8px; }
        h3 { font-size: 11px; font-weight: bold; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .info-table { border: none; }
        .info-table strong { font-weight: bold; }
        .info-table td { border: none; padding: 2px 0; }
        .signatures { margin-top: 25px; border: none; }
        .signatures td { border: none; padding: 5px 0; }
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
                <td style="border: none; text-align: center; padding-bottom: 10px;">
                    <img src="{{ public_path('images/header.png') }}" style="width: 100%; height: auto;">
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
        <div style="text-align: right; margin-bottom: 20px;">
            <strong>A Liofilchem ® s.r.l.- Direzione Aziendale</strong><br>
            Via Uruguay 64026,<br>
            Roseto degli Abruzzi (TE) Italia
        </div>

        <div style="text-align: center; margin-bottom: 20px; font-size: 12px;">
            <strong>N. RAPPORTO DI PROVA:</strong> {{ $acceptance->acceptance_number }}_0<br>
            <strong>Data Rapporto di Prova:</strong> {{ $reportDate }}
        </div>

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
            <h3>1. MA_09_Misurazione del pH</h3>
            <table class="info-table">
                <tr><td style="width: 25%;"><strong>Metodo di Prova:</strong></td><td style="width: 75%;">{{ $methodRevisions['test_a']->revision_string ?? 'N/D' }}</td></tr>
                <tr><td><strong>ID Campione:</strong></td><td>{{ $acceptance->plates[0] ?? 'N/D' }}</td></tr>
                <tr><td><strong>ID pH-metro:</strong></td><td>{{ $testAResult->ph_meter ?? 'N/D' }}</td></tr>
                <tr><td><strong>Sonda pH:</strong></td><td>{{ $testAResult->ph_probe ?? 'N/D' }}</td></tr>
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
            <h3>2. MA_60_Valutazione produttività XLD</h3>
            <table class="info-table">
                <tr><td style="width: 25%;"><strong>Metodo di Prova:</strong></td><td style="width: 75%;">{{ $methodRevisions['test_c']->revision_string ?? 'N/D' }}</td></tr>
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
            <h3>3. MA_61_Contaminazione microbica</h3>
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
                <tr><td style="width: 25%;"><strong>Metodo di Prova:</strong></td><td style="width: 75%;">{{ $methodRevisions['test_b']->revision_string ?? 'N/D' }}</td></tr>
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

        <div style="margin-top: 30px;">
            <hr style="border: 0; border-top: 1px solid #ccc; margin-bottom: 15px;">
            <h3>NOTA:</h3>
            <p style="font-size: 9px; text-align: justify;">
                L'incertezza riportata in questo documento è l'incertezza estesa ottenuta moltiplicando l'incertezza tipo composta per un fattore di
                copertura
                k = 2, per una distribuzione normale e un livello di confidenza di circa il 95%.<br><br>
                Le Specifiche riportate nel presente documento sono definite da Liofilchem Srl.<br><br>
                I risultati contenuti nel presente rapporto si riferiscono esclusivamente al campione provato.<br><br>
                Il presente rapporto può essere riprodotto solo integralmente.<br><br>
                Il campione è stato sottoposto alle prove come pervenuto in laboratorio.<br><br>
                Il laboratorio declina ogni responsabilità relativa alle informazioni fornite dal cliente riportate nel presente Rapporto che possono avere
                influenza sulla validità dei risultati.<br><br>
                (1)Dato fornito dal cliente
            </p>
        </div>
    </main>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 10;
            $pageText = "Pagina " . $PAGE_NUM . " di " . $PAGE_COUNT;
            $text_width = $fontMetrics->get_text_width($pageText, $font, $size);
            // Posiziona il testo nell'angolo in alto a destra, tenendo conto dei margini
            $x = $pdf->get_width() - $text_width - 40; // 40px è il margine destro
            $y = 15; // Posizione verticale dall'alto, all'interno del margine superiore
            $pdf->page_text($x, $y, $pageText, $font, $size);
        }
    </script>
</body>
</html>