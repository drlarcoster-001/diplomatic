<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/personal/carnet.php
 * Propósito: Carnet institucional tamaño tarjeta estándar CR80 (85.6 x 54mm).
 * Versión: 3.0.0
 *
 * @var array $persona
 */
$p        = $persona;
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$avatar = !empty($p['foto'])
    ? '/diplomatic/public/' . ltrim($p['foto'], '/')
    : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . '+' . $p['last_name']) . '&background=a855f7&color=fff&size=200&bold=true';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carnet — <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #2d2d2d;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* Barra superior solo en pantalla */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .btn {
            padding: 9px 22px;
            border-radius: 20px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print  { background: #a855f7; color: white; }
        .btn-back   { background: rgba(255,255,255,0.15); color: white; text-decoration: none; display:inline-block; padding: 9px 18px; border-radius: 20px; font-size:13px; }

        /* El carnet en pantalla se muestra grande para que se vea bien */
        .carnet-screen-wrapper {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        /* TAMAÑO REAL: 85.6mm x 54mm — se escala en pantalla x3 para verlo bien */
        .carnet {
            width: 256.8px;   /* 85.6mm * 3 */
            height: 162px;    /* 54mm * 3 */
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .carnet-body {
            display: flex;
            flex: 1;
        }

        /* Franja izquierda */
        .carnet-sidebar {
            width: 72px;
            background: linear-gradient(180deg, #a855f7 0%, #7c3aed 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 14px 6px 10px;
            flex-shrink: 0;
        }

        .carnet-foto {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.85);
            object-fit: cover;
            margin-bottom: 8px;
        }

        .carnet-inst {
            font-size: 6.5px;
            color: rgba(255,255,255,0.92);
            text-align: center;
            line-height: 1.4;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .carnet-inst small {
            display: block;
            font-size: 5.5px;
            opacity: 0.75;
            margin-top: 3px;
            font-weight: 400;
            text-transform: none;
        }

        /* Contenido derecho */
        .carnet-content {
            flex: 1;
            padding: 14px 14px 10px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .carnet-nombre {
            font-size: 10px;
            font-weight: 800;
            color: #1a1a2e;
            line-height: 1.25;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .carnet-badge {
            display: inline-block;
            background: linear-gradient(135deg, #a855f7, #7c3aed);
            color: white;
            font-size: 7px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }

        .carnet-campos {
            display: flex;
            flex-direction: column;
            gap: 3px;
            border-top: 1px solid #f0e8ff;
            padding-top: 8px;
        }

        .carnet-campo {
            display: flex;
            gap: 5px;
            align-items: baseline;
        }

        .campo-label {
            font-size: 6px;
            color: #a78bfa;
            font-weight: 700;
            text-transform: uppercase;
            min-width: 38px;
            letter-spacing: 0.3px;
        }

        .campo-valor {
            font-size: 7px;
            color: #374151;
            font-weight: 500;
        }

        /* Footer strip */
        .carnet-footer {
            height: 14px;
            background: linear-gradient(90deg, #7c3aed, #a855f7);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
        }

        .carnet-footer span {
            font-size: 6px;
            color: rgba(255,255,255,0.85);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* IMPRESIÓN */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
                display: block;
            }

            .toolbar { display: none !important; }

            .carnet-screen-wrapper {
                background: white;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
                display: block;
            }

            /* En impresión el carnet va a tamaño real */
            .carnet {
                width: 85.6mm;
                height: 54mm;
                border-radius: 4mm;
                border: 0.3mm solid #ddd;
                position: absolute;
                top: 10mm;
                left: 50%;
                transform: translateX(-50%);
            }

            .carnet-sidebar { width: 22mm; }
            .carnet-foto    { width: 16mm; height: 16mm; margin-bottom: 3mm; }
            .carnet-inst    { font-size: 4.5px; }
            .carnet-inst small { font-size: 4px; }
            .carnet-content { padding: 4mm 4mm 3mm 3mm; }
            .carnet-nombre  { font-size: 7px; margin-bottom: 1.5mm; }
            .carnet-badge   { font-size: 5px; padding: 1px 4px; margin-bottom: 2.5mm; }
            .carnet-campos  { gap: 1mm; padding-top: 2mm; }
            .campo-label    { font-size: 4.5px; min-width: 10mm; }
            .campo-valor    { font-size: 5px; }
            .carnet-footer  { height: 4mm; }
            .carnet-footer span { font-size: 4px; }

            @page {
                size: A4;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="javascript:history.back()" class="btn-back">← Volver</a>
    <button class="btn btn-print" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
</div>

<div class="carnet-screen-wrapper">
    <div class="carnet">

        <div class="carnet-body">

            <div class="carnet-sidebar">
                <img src="<?= htmlspecialchars($avatar) ?>" class="carnet-foto" alt="Foto"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($p['first_name'] . '+' . $p['last_name']) ?>&background=a855f7&color=fff&size=200'">
                <div class="carnet-inst">
                    Decanato<br>Ciencias<br>de la Salud
                    <small>UCLA · Diplomados</small>
                </div>
            </div>

            <div class="carnet-content">
                <div>
                    <div class="carnet-nombre"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                    <span class="carnet-badge"><?= htmlspecialchars($p['tipo_nombre']) ?></span>
                </div>

                <div class="carnet-campos">
                    <div class="carnet-campo">
                        <span class="campo-label">Cédula</span>
                        <span class="campo-valor"><?= htmlspecialchars($p['document_id']) ?></span>
                    </div>
                    <?php if (!empty($p['email'])): ?>
                    <div class="carnet-campo">
                        <span class="campo-label">Email</span>
                        <span class="campo-valor"><?= htmlspecialchars($p['email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($p['telefono_celular'])): ?>
                    <div class="carnet-campo">
                        <span class="campo-label">Teléfono</span>
                        <span class="campo-valor"><?= htmlspecialchars($p['telefono_celular']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($p['fecha_inicio'])): ?>
                    <div class="carnet-campo">
                        <span class="campo-label">Desde</span>
                        <span class="campo-valor"><?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="carnet-footer">
            <span>Programa de Diplomados</span>
            <span><?= date('Y') ?></span>
        </div>

    </div>
</div>

<script>
// Auto-abre el diálogo de impresión si viene con ?print=1
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.onload = () => window.print();
}
</script>

</body>
</html>