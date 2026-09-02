<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Rapporto di Prova {{ $acceptance->acceptance_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
        @page { margin: 220px 40px 70px 40px; }
        header { position: fixed; top: -190px; left: 0px; right: 0px; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 60px; text-align: left; font-size: 8px; border-top: 1px solid #ccc; padding-top: 5px; }
        .footer-revision { margin-bottom: 8px; }
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
        .signatures td { border: none; padding: 30px 0 5px; }
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
        .test-section {
            page-break-inside: avoid;
        }
        .test-section + .test-section {
            margin-top: 12px;
        }
        .notes-section {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @php
        $approvalDate = null;
        $reportDate = $report_date; // Fallback to original report date
        $watermarkText = '';
        $last_sign = null; // Inizializzazione della nuova variabile
        $doubleTests = is_array($acceptance->double_tests ?? null) ? $acceptance->double_tests : [];
        $hasDoubleTestA = in_array('test1', $doubleTests);
        $hasDoubleTestB = in_array('test2', $doubleTests);
        $hasDoubleTestC = in_array('test3', $doubleTests);

        // Calcola la data di scadenza sommando 6 mesi alla data di campionamento
        $calculatedExpiryDate = \Carbon\Carbon::parse($acceptance->sampling_date)->addDays(180);

        // Raccogli le date di firma del tecnico (lab_signed_at)
        $labSignedDates = [];
        if ($testAResult && $testAResult->lab_signed_at) {
            $labSignedDates[] = $testAResult->lab_signed_at;
        }
        if ($testBResult && $testBResult->lab_signed_at) {
            $labSignedDates[] = $testBResult->lab_signed_at;
        }
        if ($testCResult && $testCResult->lab_signed_at) {
            $labSignedDates[] = $testCResult->lab_signed_at;
        }
        if (!empty($labSignedDates)) $last_sign = max($labSignedDates); // Prende la data più recente
  

        $isAnnulledAndUntouched = false;
        if ($acceptance->annulled_at) {
            // L'annullamento azzera le firme. Se una firma è presente, significa che è stata apposta un'azione.
            $isActionTaken = ($testAResult && $testAResult->lab_signed_at) ||
                             ($testBResult && $testBResult->lab_signed_at) ||
                             ($testCResult && $testCResult->lab_signed_at) ||
                             ($testAResult && $testAResult->rl_signed_at) ||
                             ($testBResult && $testBResult->rl_signed_at) ||
                             ($testCResult && $testCResult->rl_signed_at);

            // Se non ci sono firme, controlliamo se c'è stata una modifica manuale.
            if (!$isActionTaken) {
                $annulmentDate = \Carbon\Carbon::parse($acceptance->annulled_at);
                // Un piccolo buffer temporale aiuta a distinguere una modifica manuale dall'aggiornamento automatico durante l'annullamento.
                $annulmentBufferDate = $annulmentDate->copy()->addSeconds(2);
                if (($testAResult && $testAResult->updated_at->gt($annulmentBufferDate)) ||
                    ($testBResult && $testBResult->updated_at->gt($annulmentBufferDate)) ||
                    ($testCResult && $testCResult->updated_at->gt($annulmentBufferDate))) {
                    $isActionTaken = true;
                }
            }

            if (!$isActionTaken) {
                $isAnnulledAndUntouched = true;
            }
        }

        if ($isAnnulledAndUntouched) {
            $watermarkText = 'ANNULLATO il ' . \Carbon\Carbon::parse($acceptance->annulled_at)->format('d.m.Y');
        } elseif ($isPdfComplete) {
            // Il PDF è completo
            if ($last_sign!=null) {
                $last_sign=$last_sign->format('d.m.Y');
                $approvalDate = $last_sign;
                $reportDate = $approvalDate;
            }
        } else {
            // Il PDF non è completo, determiniamo la filigrana corretta.
            $hasAnyRlSignedTest = (in_array('test1', $acceptance->tests) && $testAResult && $testAResult->rl_signed_at) ||
                                  (in_array('test2', $acceptance->tests) && $testBResult && $testBResult->rl_signed_at) ||
                                  (in_array('test3', $acceptance->tests) && $testCResult && $testCResult->rl_signed_at);

            if ($hasAnyRlSignedTest) {
                $watermarkText = 'RdP incompleto';
            } else {
                $watermarkText = 'Anteprima non validata';
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
            <tr>
                <td style="border: none; text-align: center; padding: 0; font-size: 12px;">
                    <strong>N. RAPPORTO DI PROVA:</strong> {{ $acceptance->acceptance_number }}@if($isPdfComplete)_{{ $pdfRevisionCount }}@endif<br>
                    <strong>Data Rapporto di Prova:</strong> {{ $reportDate }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <div class="footer-revision">RdP rev.4 / 16.03.2026</div>
        <div>Liofilchem® s.r.l. Via Uruguay - 64026, Roseto degli Abruzzi (TE) Italia - Tel +39 0858930745 - Fax +39 0858930330</div>
    </footer>

    @if(!$isPdfComplete)
        <div class="watermark">{{ $watermarkText }}</div>
    @endif

    <main>
        @if($pdfRevisionCount > 0)
            <div style="margin: 0 0 20px; font-size: 9px; line-height: 1.2; text-align: center;">
                Questo Rapporto di Prova annulla e sostituisce il RdP {{ $previousReportNumber }}.
                @if(!empty($replacementReason))<br>Motivo della sostituzione: {{ $replacementReason }}@endif
            </div>
        @endif

        <div style="text-align: right; margin-top: 12px; margin-bottom: 10px;">
            <strong>A Liofilchem<sup>®</sup> s.r.l. - Direzione Aziendale</strong><br>
            Via Uruguay 64026,<br>
            Roseto degli Abruzzi (TE) Italia
        </div>

        <table class="info-table">
            <tr>
                <td style="width: 25%;"><strong>N. Accettazione:</strong></td>
                <td style="width: 75%;">{{ $acceptance->acceptance_number }}</td>
            </tr>
            <tr>
                <td><strong>Data di campionamento<sup>(1)</sup>:</strong></td>
                <td>{{ \Carbon\Carbon::parse($acceptance->sampling_date)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
            </tr>
            <tr>
                <td><strong>Data accettazione:</strong></td>
                <td>{{ \Carbon\Carbon::parse($acceptance->acceptance_date)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td style="width: 25%;"><strong>Prodotto<sup>(1)</sup>:</strong></td>
                <td style="width: 75%;">{{ $productInfo['name'] }}</td>
            </tr>
            <tr>
                <td><strong>Codice<sup>(1)</sup>:</strong></td>
                <td>{{ $productInfo['code'] }}</td>
            </tr>
            <tr>
                <td><strong>Lotto<sup>(1)</sup>:</strong></td>
                <td>{{ $acceptance->lotto }}</td>
            </tr>
            <tr>
                <td><strong>Data di scadenza<sup>(1)</sup>:</strong></td>
                <td>{{ $calculatedExpiryDate->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
            </tr>
        </table>

        {{-- 1. Controllo del pH (Test A) --}}
        @if($testAResult)
            <div class="test-section">
                <h3>Controllo del pH - Metodo di Prova {{ $methodRevisions['test_a']->revision_string ?? 'N/D' }}</h3>
                <table class="info-table">
                    <tr><td><strong>ID Campioni:</strong></td><td>{{ $acceptance->plates[0] ?? 'N/D' }}</td></tr>
                    @if($hasDoubleTestA)
                        <tr><td><strong>ID Campione (Doppio):</strong></td><td>{{ $acceptance->plates[2] ?? 'N/D' }}</td></tr>
                    @endif

                </table>
                <table>
                    <thead><tr><th>Parametro</th><th>Specifiche</th><th>Risultato e Incertezza</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>Valore di pH (25°C)</td>
                            <td>7.4 ± 0.2</td>
                            <td>{{ number_format($testAResult->ph_value, 2) }} ± 0.2</td> {{-- 'INC' è un placeholder --}}
                        </tr>
                    </tbody>
                </table>
                @if($hasDoubleTestA)
                    <table style="margin-top:6px;">
                        <thead><tr><th>Parametro (Doppio)</th><th>Specifiche</th><th>Risultato e Incertezza</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Valore di pH (25°C)</td>
                                <td>7.4 ± 0.2</td>
                                <td>{{ isset($testAResult->ph_value_double) ? number_format($testAResult->ph_value_double, 2) . ' ± 0.2' : 'N/D' }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
                <table class="info-table" style="margin-top: 5px;">
                    <tr>
                        <td style="width: 25%;"><strong>Data Inizio Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testAResult->test_date)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
                        <td style="width: 25%;"><strong>Data Fine Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testAResult->test_date)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
                    </tr>
                </table>
            </div>
        @endif

        {{-- 2. Produttività, Metodo Qualitativo (Test C) --}}
        @if($testCResult)
            <div class="test-section">
                <h3>Produttività, Metodo Qualitativo - Metodo di Prova {{ $methodRevisions['test_c']->revision_string ?? 'N/D' }}</h3>
                <table class="info-table">
                    <tr><td><strong>ID Campioni:</strong></td><td>{{ $testCResult->plate_id_start_lotto ?? 'N/D' }}, {{ $testCResult->plate_id_mid_lotto ?? 'N/D' }}, {{ $testCResult->plate_id_end_lotto ?? 'N/D' }}</td></tr>
                    @if($hasDoubleTestC)
                        <tr><td><strong>Indicazioni piastre (Doppio):</strong></td><td>{{ $testCResult->plate_id_start_lotto_run2 ?? 'N/D' }}, {{ $testCResult->plate_id_mid_lotto_run2 ?? 'N/D' }}, {{ $testCResult->plate_id_end_lotto_run2 ?? 'N/D' }}</td></tr>
                    @endif
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
                @if($hasDoubleTestC)
                    <table style="margin-top:6px;">
                        <thead><tr><th>Ceppo di controllo (Doppio)</th><th>Incubazione</th><th>Specifiche</th><th>Risultato</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Salmonella typhimurium ATCC 14028</td>
                                <td>24 ± 3h a 35 ± 2°C</td>
                                <td>Colonie rosse con centro nero</td>
                                <td>{{ isset($testCResult->tsa_growth_result_run2) ? ($testCResult->tsa_growth_result_run2 == 'rilevata' ? 'Rilevata' : 'Non Rilevata') : 'N/D' }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
                <table class="info-table" style="margin-top: 5px;">
                    <tr>
                        <td style="width: 25%;"><strong>Data Inizio Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testCResult->test_start_datetime)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
                        <td style="width: 25%;"><strong>Data Fine Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testCResult->test_end_datetime)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
                    </tr>
                </table>
            </div>
        @endif

        {{-- 3. Controllo della contaminazione microbica (Test B) --}}
        @if($testBResult)
            <div class="test-section">
                <h3>Controllo della contaminazione microbica, Metodo Qualitativo - Metodo di Prova {{ $methodRevisions['test_b']->revision_string ?? 'N/D' }}</h3>
                <table class="info-table">
                    <tr><td style="width: 25%;"><strong>ID Campioni:</strong></td><td><strong>35 ± 2°C, 7 giorni:</strong> {{ implode(' – ', $platesB35) }}</td></tr>
                    <tr><td></td><td><strong>22,5 ± 2,5°C, 7 giorni:</strong> {{ implode(' – ', $platesB22) }}</td></tr>
                    @if($hasDoubleTestB)
                        <tr><td><strong>ID Campioni (Doppio):</strong></td><td><strong>35 ± 2°C, 7 giorni:</strong> {{ $testBResult->plate_id_start_plate1_35_run2 ?? 'N/D' }} / {{ $testBResult->plate_id_start_plate2_35_run2 ?? 'N/D' }} · {{ $testBResult->plate_id_mid_plate1_35_run2 ?? 'N/D' }} / {{ $testBResult->plate_id_mid_plate2_35_run2 ?? 'N/D' }} · {{ $testBResult->plate_id_end_plate1_35_run2 ?? 'N/D' }} / {{ $testBResult->plate_id_end_plate2_35_run2 ?? 'N/D' }}</td></tr>
                        <tr><td></td><td><strong>22,5 ± 2,5°C, 7 giorni:</strong> {{ $testBResult->plate_id_start_plate1_22_run2 ?? 'N/D' }} / {{ $testBResult->plate_id_start_plate2_22_run2 ?? 'N/D' }} · {{ $testBResult->plate_id_mid_plate1_22_run2 ?? 'N/D' }} / {{ $testBResult->plate_id_mid_plate2_22_run2 ?? 'N/D' }} · {{ $testBResult->plate_id_end_plate1_22_run2 ?? 'N/D' }} / {{ $testBResult->plate_id_end_plate2_22_run2 ?? 'N/D' }}</td></tr>
                    @endif
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
                            <td>22,5 ± 2,5°C, 7 giorni</td>
                            <td>Nessuna crescita</td>
                            <td>{{ $growth_22 }}</td>
                        </tr>
                        @if($hasDoubleTestB)
                            @php
                                $growth_35_run2 = 'Nessuna crescita';
                                if (
                                    ($testBResult->growth_result_35_start_plate1_run2 ?? null) == 'rilevata' || ($testBResult->growth_result_35_start_plate2_run2 ?? null) == 'rilevata' ||
                                    ($testBResult->growth_result_35_mid_plate1_run2 ?? null) == 'rilevata'   || ($testBResult->growth_result_35_mid_plate2_run2 ?? null) == 'rilevata'   ||
                                    ($testBResult->growth_result_35_end_plate1_run2 ?? null) == 'rilevata'   || ($testBResult->growth_result_35_end_plate2_run2 ?? null) == 'rilevata'
                                ) { $growth_35_run2 = 'Crescita rilevata'; }

                                $growth_22_run2 = 'Nessuna crescita';
                                if (
                                    ($testBResult->growth_result_22_start_plate1_run2 ?? null) == 'rilevata' || ($testBResult->growth_result_22_start_plate2_run2 ?? null) == 'rilevata' ||
                                    ($testBResult->growth_result_22_mid_plate1_run2 ?? null) == 'rilevata'   || ($testBResult->growth_result_22_mid_plate2_run2 ?? null) == 'rilevata'   ||
                                    ($testBResult->growth_result_22_end_plate1_run2 ?? null) == 'rilevata'   || ($testBResult->growth_result_22_end_plate2_run2 ?? null) == 'rilevata'
                                ) { $growth_22_run2 = 'Crescita rilevata'; }
                            @endphp
                            <tr>
                                <td>(Doppio) - 35 ± 2°C, 7 giorni</td>
                                <td>Nessuna crescita</td>
                                <td>{{ $growth_35_run2 }}</td>
                            </tr>
                            <tr>
                                <td>(Doppio) - 22,5 ± 2,5°C, 7 giorni</td>
                                <td>Nessuna crescita</td>
                                <td>{{ $growth_22_run2 }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <table class="info-table" style="margin-top: 5px;">
                    <tr>
                        <td style="width: 25%;"><strong>Data Inizio Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testBResult->test_start_datetime)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
                        <td style="width: 25%;"><strong>Data Fine Analisi:</strong></td><td style="width: 25%;">{{ \Carbon\Carbon::parse($testBResult->test_end_datetime)->format('d.m.Y') }}</td> {{-- Usa il fuso orario globale --}}
                    </tr>
                </table>
            </div>
        @endif

        <table class="signatures">
            <tr>
                <td style="width: 33.33%;">
                    <strong>Approvato il:</strong> {{ $approvalDate ?? '________________' }}
                </td>
                <td style="width: 33.33%;">
                    @if($isPdfComplete)
                        <strong>Firma:</strong> Documento firmato elettronicamente
                    @else
                        <strong>Firma:</strong> _________________________
                    @endif
                </td>
                <td style="width: 33.33%;">
                    <strong>Responsabile di Laboratorio:</strong>
                    {{ $approvalDate ? 'Dott. F. D’Emidio' : '_________________________' }}
                </td>
            </tr>
        </table>

        <div class="notes-section" style="margin-top: 120px; padding-top: 12px;">
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
            // Metodo robusto per la numerazione: usiamo i placeholder speciali di dompdf.
            // Questi vengono sostituiti correttamente durante la seconda passata di rendering.
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 10;
            $pageText = "Pagina {PAGE_NUM} di {PAGE_COUNT}";
            // Per l'allineamento a destra, calcoliamo la larghezza di un testo di esempio (il più lungo possibile).
            $text_width = $fontMetrics->get_text_width("Pagina 10 di 10", $font, $size);
            $x = $pdf->get_width() - $text_width - 40;
            $y = 15;
            $pdf->page_text($x, $y, $pageText, $font, $size);
        }
    </script>
</body>
</html>
