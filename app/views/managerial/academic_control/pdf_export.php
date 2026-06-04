<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / CONTROL ACADÉMICO
 * ARCHIVO: app/views/managerial/academic_control/pdf_export.php
 * PROPÓSITO: Template ejecutivo para el reporte de trazabilidad académica (PDF Limpio).
 * VERSIÓN: 1.0.0 - Lanzamiento: Soporte para agrupamiento por diplomado y estatus dinámicos.
 */

// 1. Preparación de Imágenes en Base64 para Dompdf
$pathUcla = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
$pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';

$imgUcla = file_exists($pathUcla) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pathUcla)) : '';
$imgMedicina = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

/**
 * Variables recibidas desde el Controlador:
 * @var array $data    -> Matriz completa de estudiantes (sin paginación)
 * @var array $filters -> Filtros aplicados para el encabezado
 * @var string $title  -> Título del reporte
 */

// Agrupar la data por Diplomado para generar secciones automáticas
$groupedData = [];
foreach ($data as $row) {
    $groupedData[$row['diplomado']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0.8cm; }
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 7.5pt; 
            color: #333; 
            line-height: 1.2;
        }
        
        .page-break { page-break-after: always; }
        
        /* Encabezado */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-text { 
            text-align: center; 
            font-weight: bold; 
            font-size: 9pt; 
            text-transform: uppercase;
        }

        .report-title { 
            text-align: center; 
            font-size: 14pt; 
            font-weight: bold; 
            margin-bottom: 5px; 
            color: #1a3a5a;
            text-transform: uppercase;
        }

        .filter-info {
            text-align: center;
            font-size: 8pt;
            color: #555;
            margin-bottom: 20px;
            font-style: italic;
        }

        .section-banner {
            background-color: #f1f5f9;
            padding: 8px 12px;
            border-left: 4pt solid #007bff;
            margin-bottom: 15px;
            font-size: 10pt;
            font-weight: bold;
            color: #1a3a5a;
            text-transform: uppercase;
        }

        /* Tablas */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .data-table th { 
            background-color: #1a3a5a; 
            color: #ffffff;
            border: 0.1pt solid #1a3a5a; 
            padding: 6px 4px; 
            font-size: 6.5pt; 
            text-transform: uppercase;
        }
        .data-table td { border: 0.1pt solid #ddd; padding: 6px 4px; vertical-align: middle; }
        
        /* Estatus Colores */
        .badge {
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 6pt;
            text-transform: uppercase;
        }
        .status-activo { color: #27ae60; }
        .status-cursando { color: #2980b9; }
        .status-rechazado { color: #c0392b; }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .bg-light { background-color: #f8f9fa; }
        .code-text { font-family: 'Courier', monospace; font-weight: bold; color: #34495e; }

        .footer-info { 
            position: fixed; 
            bottom: 0; 
            width: 100%; 
            font-size: 7pt; 
            color: #777; 
            text-align: right;
            border-top: 0.5pt solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="60px"><img src="<?= $imgUcla ?>" style="width: 60px;"></td>
            <td class="header-text">
                UNIVERSIDAD CENTROCCIDENTAL “LISANDRO ALVARADO”<br>
                DECANATO DE CIENCIAS DE LA SALUD | COORDINACIÓN DE EXTENSIÓN<br>
                <span style="font-size: 7pt; font-weight: normal;">COORDINACION DE DIPLOMADOS</span>
            </td>
            <td width="60px" align="right"><img src="<?= $imgMedicina ?>" style="width: 60px;"></td>
        </tr>
    </table>

    <div class="report-title"><?= $title ?></div>
    <div class="filter-info">
        Filtros aplicados: 
        [Estudiante: <?= $filters['student'] ?: 'TODOS' ?>] | 
        [Estatus: <?= $filters['participant_status'] ?: 'TODOS' ?>] | 
        [Fecha: <?= date('d/m/Y') ?>]
    </div>

    <?php 
    $totalGeneral = 0;
    foreach($groupedData as $diplomado => $estudiantes): 
        $totalGeneral += count($estudiantes);
    ?>
        <div class="section-banner">PROGRAMA: <?= $diplomado ?> (<?= count($estudiantes) ?> Registros)</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th width="18%">Participante</th>
                    <th width="8%">Cédula</th>
                    <th width="10%">Grupo</th>
                    <th width="15%">Trazabilidad Adm/Fin</th>
                    <th width="12%">Código</th>
                    <th width="5%">C.I.</th>
                    <th width="5%">C.E.</th>
                    <th width="10%">Estatus Ficha</th>
                    <th width="10%">Estatus Matrícula</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($estudiantes as $e): ?>
                <tr>
                    <td class="fw-bold"><?= $e['participante'] ?></td>
                    <td class="text-center"><?= $e['cedula'] ?></td>
                    <td class="text-center" style="color: #2980b9;"><?= $e['nombre_grupo'] ?></td>
                    <td class="text-center" style="font-size: 6pt;"><?= $e['trazabilidad_adm_fin'] ?></td>
                    <td class="text-center code-text"><?= $e['codigo_estudiante'] ?></td>
                    <td class="text-center"><?= $e['nro_const_inscripcion'] ?></td>
                    <td class="text-center"><?= $e['nro_const_estudios'] ?></td>
                    <td class="text-center fw-bold <?= ($e['estatus_ficha'] == 'ACTIVO') ? 'status-activo' : '' ?>">
                        <?= $e['estatus_ficha'] ?>
                    </td>
                    <td class="text-center fw-bold <?= ($e['estatus_matricula'] == 'CURSANDO') ? 'status-cursando' : '' ?>">
                        <?= $e['estatus_matricula'] ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <div class="footer-info">
        Generado por: <?= $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'] ?> | 
        Página 1 de 1 | 
        Total Consolidado: <?= $totalGeneral ?> estudiantes.
    </div>

</body>
</html>