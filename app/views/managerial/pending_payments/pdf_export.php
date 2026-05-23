<?php
/**
 * MÓDULO: PANEL GERENCIAL / PAGOS PENDIENTES
 * ARCHIVO: app/views/managerial/pending_payments/pdf_export.php
 * PROPÓSITO: Template ejecutivo multipágina para reporte de pagos en tránsito (PDF Limpio).
 * VERSIÓN: 1.0.0 - Creación de plantilla con agrupación por diplomado, formato regional y totalizador.
 */

// 1. Preparación de Imágenes en Base64
$pathUcla = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
$pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';

$imgUcla = file_exists($pathUcla) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pathUcla)) : '';
$imgMedicina = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

/**
 * Variables recibidas desde el Controlador (vía extract):
 * @var array $fullData     -> Todos los pagos pendientes listos para imprimir
 * @var array $groupedData  -> Pagos agrupados por nombre de diplomado
 * @var float $totalPendingUsd -> Sumatoria total de equivalentes en USD
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
            font-size: 8.5pt; 
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
            padding: 10px;
            border-left: 4pt solid #fd7e14;
            margin-bottom: 15px;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Tablas de Datos */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th { 
            background-color: #f2f2f2; 
            border: 0.5pt solid #ccc; 
            padding: 7px; 
            font-size: 7.5pt; 
            text-transform: uppercase;
        }
        .data-table td { border: 0.5pt solid #eee; padding: 6px; }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-orange { color: #e67e22; }
        .text-danger { color: #dc3545; }
        .bg-total { background-color: #fdf2e9; }
        .small-text { font-size: 6.5pt; }

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

    <div class="report-title">Auditoría de Pagos en Tránsito</div>
    <div class="section-banner">CONSOLIDADO GENERAL DE PAGOS PENDIENTES</div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="8%">Cédula</th>
                <th width="18%">Participante</th>
                <th width="15%">Diplomado</th>
                <th>Origen</th>
                <th>Método</th>
                <th>Moneda</th>
                <th class="text-right">Monto Orig.</th>
                <th class="text-right">Tasa</th>
                <th class="bg-total text-right">Equiv. USD</th>
                <th width="15%">Observación</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fullData as $r): ?>
            <tr>
                <td class="text-center"><?= $r['cedula'] ?></td>
                <td class="fw-bold" style="font-size: 7.5pt;"><?= $r['estudiante'] ?></td>
                <td class="text-center small-text"><?= $r['diplomado'] ?></td>
                <td class="text-center small-text"><?= $r['origin'] ?></td>
                <td class="text-center fw-bold"><?= $r['tipo_pago'] ?></td>
                <td class="text-center"><?= $r['moneda'] ?></td>
                <td class="text-right"><?= number_format((float)$r['monto'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$r['tasa'], 2, ',', '.') ?></td>
                <td class="text-right fw-bold bg-total">$ <?= number_format((float)$r['monto_usd'], 2, ',', '.') ?></td>
                <td class="text-center small-text <?= $r['tipo_pago'] === 'EFECTIVO' ? 'text-danger fw-bold' : 'text-orange' ?>">
                    <?= $r['observacion'] ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="8" class="text-right fw-bold">TOTAL DINERO FLOTANTE (USD):</td>
                <td class="text-right fw-bold" style="font-size: 10pt;">$ <?= number_format($totalPendingUsd, 2, ',', '.') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="footer-info">Generado el <?= date('d/m/Y h:i A') ?> | Reporte de Control Interno</div>

    <?php if(!empty($groupedData)): foreach($groupedData as $nombreDiplomado => $pagos): ?>
        <div class="page-break"></div>
        <div class="section-banner">DETALLE EN TRÁNSITO: <?= strtoupper($nombreDiplomado) ?></div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th width="10%">Cédula</th>
                    <th width="25%">Estudiante</th>
                    <th>Origen</th>
                    <th>Método</th>
                    <th>Moneda</th>
                    <th class="text-right">Monto Orig.</th>
                    <th class="text-right">Tasa</th>
                    <th class="bg-total text-right">Equiv. USD</th>
                    <th width="18%">Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotalD = 0;
                foreach($pagos as $p): 
                    $subtotalD += (float)$p['monto_usd'];
                ?>
                <tr>
                    <td class="text-center"><?= $p['cedula'] ?></td>
                    <td class="fw-bold"><?= $p['estudiante'] ?></td>
                    <td class="text-center small-text"><?= $p['origin'] ?></td>
                    <td class="text-center fw-bold"><?= $p['tipo_pago'] ?></td>
                    <td class="text-center"><?= $p['moneda'] ?></td>
                    <td class="text-right"><?= number_format((float)$p['monto'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format((float)$p['tasa'], 2, ',', '.') ?></td>
                    <td class="text-right fw-bold bg-total">$ <?= number_format((float)$p['monto_usd'], 2, ',', '.') ?></td>
                    <td class="text-center small-text <?= $p['tipo_pago'] === 'EFECTIVO' ? 'text-danger fw-bold' : 'text-orange' ?>">
                        <?= $p['observacion'] ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-total">
                    <td colspan="7" class="text-right fw-bold">SUBTOTAL FLOTANTE DEL PROGRAMA:</td>
                    <td class="text-right fw-bold text-orange">$ <?= number_format($subtotalD, 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endforeach; endif; ?>

</body>
</html>