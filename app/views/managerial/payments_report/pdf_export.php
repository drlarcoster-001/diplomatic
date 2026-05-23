<?php
/**
 * MÓDULO: PANEL GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/views/managerial/payments_report/pdf_export.php
 * PROPÓSITO: Template ejecutivo multipágina para reporte consolidado de recaudación (PDF Limpio).
 * VERSIÓN: 2.3.0 - ACTUALIZADO: Inclusión de campo Grupos en Matriz y Detalle Individual.
 */

// 1. Preparación de Imágenes en Base64
$pathUcla = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
$pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';

$imgUcla = file_exists($pathUcla) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pathUcla)) : '';
$imgMedicina = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

/**
 * Variables recibidas desde el Controlador:
 * @var array $summary      -> Totales agrupados por Diplomado (Hoja 1)
 * @var array $fullMatrix   -> Todos los estudiantes (Hoja 2)
 * @var array $groupedData  -> Estudiantes agrupados por diplomado (Hojas 3+)
 * @var array $totals       -> Totales generales de caja
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 8pt; 
            color: #333; 
            line-height: 1.1;
        }
        
        .page-break { page-break-after: always; }
        
        /* Encabezado Institucional */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-text { 
            text-align: center; 
            font-weight: bold; 
            font-size: 9pt; 
            text-transform: uppercase;
        }

        .report-title { 
            text-align: center; 
            font-size: 13pt; 
            font-weight: bold; 
            margin-bottom: 5px; 
            color: #2c3e50;
            text-transform: uppercase;
        }
        .section-banner {
            background-color: #f8f9fa;
            padding: 8px;
            border-left: 4pt solid #fd7e14;
            margin-bottom: 15px;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Tablas de Datos */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th { 
            background-color: #f2f2f2; 
            border: 0.5pt solid #ccc; 
            padding: 5px; 
            font-size: 7pt; 
            text-transform: uppercase;
        }
        .data-table td { border: 0.5pt solid #eee; padding: 5px; vertical-align: middle; }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-orange { color: #e67e22; }
        .text-success { color: #27ae60; }
        .bg-total { background-color: #fdf2e9; }
        .small-text { font-size: 6.5pt; line-height: 1; }

        .footer-info { font-size: 7pt; color: #777; margin-top: 10px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="60px"><img src="<?= $imgUcla ?>" style="width: 60px;"></td>
            <td class="header-text">
                UNIVERSIDAD CENTROCCIDENTAL “LISANDRO ALVARADO”<br>
                DECANATO DE CIENCIAS DE LA SALUD | COORDINACIÓN DE EXTENSIÓN
            </td>
            <td width="60px" align="right"><img src="<?= $imgMedicina ?>" style="width: 60px;"></td>
        </tr>
    </table>

    <div class="report-title">Resumen Ejecutivo de Recaudación</div>
    
    <div class="section-banner">PARTE I: PAGOS POR DIPLOMADO (CONSOLIDADO)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th align="left">Nombre del Diplomado / Programa</th>
                <th>Est.</th>
                <th>Recaudado Real</th>
                <th>En Compromiso</th>
                <th>Total Proyectado</th>
                <th width="20%">Observación</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($summary as $s): ?>
            <tr>
                <td class="fw-bold"><?= $s['diplomado'] ?></td>
                <td class="text-center"><?= $s['total_estudiantes'] ?></td>
                <td class="text-right text-success">$ <?= number_format((float)$s['total_validado'], 2, ',', '.') ?></td>
                <td class="text-right text-orange">$ <?= number_format((float)$s['total_compromiso'], 2, ',', '.') ?></td>
                <td class="text-right fw-bold bg-total">$ <?= number_format((float)$s['total_proyectado'], 2, ',', '.') ?></td>
                <td class="text-center text-orange small-text"><?= $s['observacion_resumen'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="2" class="text-right fw-bold">TOTALES GENERALES:</td>
                <td class="text-right fw-bold text-success">$ <?= number_format((float)$totals['total_aprobado'], 2, ',', '.') ?></td>
                <td class="text-right fw-bold text-orange">$ <?= number_format((float)$totals['total_compromiso'], 2, ',', '.') ?></td>
                <td class="text-right fw-bold" style="font-size: 10pt;">$ <?= number_format((float)$totals['total_general'], 2, ',', '.') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="footer-info">Generado el <?= date('d/m/Y h:i A') ?> | Reporte de Alta Gerencia</div>
    
    <div class="page-break"></div>

    <div class="section-banner">PARTE II: MATRIZ GENERAL DE RECAUDACIÓN (TODOS LOS PROGRAMAS)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="15%">Participante</th>
                <th width="8%">Cédula</th>
                <th width="12%">Diplomado</th>
                <th width="12%">Grupos</th> <th>Insc.</th>
                <th>C1</th>
                <th>C2</th>
                <th>C3</th>
                <th>C4</th>
                <th>C5</th>
                <th class="bg-total">Total</th>
                <th width="10%">Obs.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fullMatrix as $r): ?>
            <tr>
                <td class="fw-bold" style="font-size: 7pt;"><?= $r['participante'] ?></td>
                <td class="text-center"><?= $r['cedula'] ?></td>
                <td class="text-center small-text"><?= $r['diplomado'] ?></td>
                <td class="text-center small-text fw-bold" style="color:#3498db;"><?= $r['grupos_nombres'] ?: 'N/A' ?></td> <td class="text-right"><?= number_format((float)$r['pago_inscripcion'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$r['pago_cuota_1'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$r['pago_cuota_2'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$r['pago_cuota_3'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$r['pago_cuota_4'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$r['pago_cuota_5'], 2, ',', '.') ?></td>
                <td class="text-right fw-bold bg-total"><?= number_format((float)$r['total_abonado'], 2, ',', '.') ?></td>
                <td class="text-center text-orange" style="font-size: 6pt;"><?= $r['observacion'] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php foreach($groupedData as $nombreDiplomado => $estudiantes): ?>
        <div class="page-break"></div>
        <div class="section-banner">DETALLE INDIVIDUAL: <?= strtoupper($nombreDiplomado) ?></div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th width="18%">Estudiante</th>
                    <th width="10%">Cédula</th>
                    <th width="15%">Grupos</th> <th>Inscrip.</th>
                    <th>C1</th>
                    <th>C2</th>
                    <th>C3</th>
                    <th>C4</th>
                    <th>C5</th>
                    <th class="bg-total">Total Validado</th>
                    <th width="12%">Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotalD = 0;
                foreach($estudiantes as $e): 
                    $subtotalD += (float)$e['total_abonado'];
                ?>
                <tr>
                    <td class="fw-bold"><?= $e['participante'] ?></td>
                    <td class="text-center"><?= $e['cedula'] ?></td>
                    <td class="text-center small-text fw-bold"><?= $e['grupos_nombres'] ?: 'N/A' ?></td> <td class="text-right"><?= number_format((float)$e['pago_inscripcion'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format((float)$e['pago_cuota_1'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format((float)$e['pago_cuota_2'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format((float)$e['pago_cuota_3'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format((float)$e['pago_cuota_4'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format((float)$e['pago_cuota_5'], 2, ',', '.') ?></td>
                    <td class="text-right fw-bold bg-total">$ <?= number_format((float)$e['total_abonado'], 2, ',', '.') ?></td>
                    <td class="text-center text-orange small-text"><?= $e['observacion'] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-total">
                    <td colspan="9" class="text-right fw-bold">RECAUDACIÓN LÍQUIDA DEL PROGRAMA:</td>
                    <td class="text-right fw-bold text-success">$ <?= number_format($subtotalD, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endforeach; ?>

</body>
</html>