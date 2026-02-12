<?php
/**
 * MÓDULO: CONFIGURACIÓN
 * Nombre del Archivo: app/views/settings/mail.php
 * Propósito: Vista (frontend) para configurar proveedores de correo y plantillas de correo
 *           para (1) Inscripción y (2) Documentos. Solo maqueta, sin persistencia aún.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/settings.css?v=<?= time() ?>">

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="h4 fw-bold mb-0">Correo (SMTP / POP3) y Notificaciones</h2>
            <p class="text-muted small mb-0">Configura el proveedor y define el asunto/contenido por tipo de correo.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($basePath) ?>/settings">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-primary btn-sm" disabled>
                <i class="bi bi-save2 me-1"></i> Guardar (próximamente)
            </button>
        </div>
    </div>
</div>

<!-- Tabs: Tipo de correo -->
<ul class="nav nav-tabs mb-3" id="mailTypeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-inscripcion" data-bs-toggle="tab" data-bs-target="#pane-inscripcion"
                type="button" role="tab" aria-controls="pane-inscripcion" aria-selected="true">
            Correo de Inscripción
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-documentos" data-bs-toggle="tab" data-bs-target="#pane-documentos"
                type="button" role="tab" aria-controls="pane-documentos" aria-selected="false">
            Correo de Documentos
        </button>
    </li>
</ul>

<div class="tab-content" id="mailTypeTabsContent">

    <!-- =========================
         PANE: INSCRIPCION
    ========================== -->
    <div class="tab-pane fade show active" id="pane-inscripcion" role="tabpanel" aria-labelledby="tab-inscripcion">
        <div class="row g-4">
            <!-- Col: Proveedor -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Proveedor de correo</h5>
                        <p class="text-muted small mb-3">Selecciona una sola configuración. Los campos se autocompletarán más adelante.</p>

                        <!-- Provider cards (radio) -->
                        <div class="row g-3">
                            <?php
                            $providers = [
                                ['key' => 'GMAIL', 'title' => 'Gmail', 'desc' => 'SMTP recomendado (App Password).', 'icon' => 'bi-google', 'tone' => 'primary'],
                                ['key' => 'OUTLOOK', 'title' => 'Hotmail / Outlook', 'desc' => 'SMTP Microsoft.', 'icon' => 'bi-microsoft', 'tone' => 'info'],
                                ['key' => 'YAHOO', 'title' => 'Yahoo', 'desc' => 'SMTP con contraseña de aplicación.', 'icon' => 'bi-envelope', 'tone' => 'warning'],
                                ['key' => 'CUSTOM', 'title' => 'Personalizada', 'desc' => 'SMTP / POP3 manual.', 'icon' => 'bi-gear', 'tone' => 'secondary'],
                            ];
                            ?>

                            <?php foreach ($providers as $i => $p): ?>
                                <div class="col-12">
                                    <label class="card h-100 shadow-sm p-3 mb-0 settings-card" style="cursor:pointer;">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="settings-icon-wrapper bg-<?= $p['tone'] ?> bg-opacity-10 text-<?= $p['tone'] ?>" style="width:48px;height:48px;">
                                                <i class="bi <?= htmlspecialchars($p['icon']) ?> fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['title']) ?></h6>
                                                    <input class="form-check-input"
                                                           type="radio"
                                                           name="ins_provider"
                                                           value="<?= htmlspecialchars($p['key']) ?>"
                                                           <?= $i === 0 ? 'checked' : '' ?>
                                                           data-provider-target="ins-<?= htmlspecialchars($p['key']) ?>">
                                                </div>
                                                <p class="small text-muted mb-0"><?= htmlspecialchars($p['desc']) ?></p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-4">

                        <!-- Provider config panels (only frontend) -->
                        <div id="ins-provider-panels">

                            <!-- Gmail -->
                            <div class="provider-panel" id="ins-GMAIL">
                                <h6 class="fw-bold mb-2">Configuración SMTP (Gmail)</h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Correo del remitente (From Email)</label>
                                        <input type="email" class="form-control form-control-sm" placeholder="no-reply@plataformadiplomados.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Nombre del remitente (From Name)</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="Diplomatic">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">SMTP Host</label>
                                        <input type="text" class="form-control form-control-sm" value="smtp.gmail.com" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" value="587" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <input type="text" class="form-control form-control-sm" value="TLS" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario SMTP</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="no-reply@plataformadiplomados.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">App Password</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                        <div class="form-text small">Recomendado: usar contraseña de aplicación (no tu clave real).</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Outlook/Hotmail -->
                            <div class="provider-panel d-none" id="ins-OUTLOOK">
                                <h6 class="fw-bold mb-2">Configuración SMTP (Outlook / Hotmail)</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">SMTP Host</label>
                                        <input type="text" class="form-control form-control-sm" value="smtp.office365.com" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" value="587" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <input type="text" class="form-control form-control-sm" value="TLS" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario SMTP</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="tu-correo@outlook.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Contraseña</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>

                            <!-- Yahoo -->
                            <div class="provider-panel d-none" id="ins-YAHOO">
                                <h6 class="fw-bold mb-2">Configuración SMTP (Yahoo)</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">SMTP Host</label>
                                        <input type="text" class="form-control form-control-sm" value="smtp.mail.yahoo.com" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" value="587" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <input type="text" class="form-control form-control-sm" value="TLS" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario SMTP</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="tu-correo@yahoo.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">App Password</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>

                            <!-- Custom SMTP/POP3 -->
                            <div class="provider-panel d-none" id="ins-CUSTOM">
                                <h6 class="fw-bold mb-2">Configuración Personalizada</h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Protocolo</label>
                                        <select class="form-select form-select-sm">
                                            <option value="SMTP" selected>SMTP (Enviar)</option>
                                            <option value="POP3">POP3 (Recibir)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Host</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="smtp.tu-dominio.com">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" placeholder="587">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <select class="form-select form-select-sm">
                                            <option value="TLS" selected>TLS</option>
                                            <option value="SSL">SSL</option>
                                            <option value="NONE">Ninguna</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="no-reply@plataformadiplomados.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Contraseña</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <hr class="my-4">

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="small text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                En esta fase es solo diseño. Luego activamos “Guardar” y “Enviar prueba”.
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                                <i class="bi bi-send-check me-1"></i> Enviar correo de prueba
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col: Plantilla -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Plantilla del correo (Inscripción)</h5>
                        <p class="text-muted small mb-3">Define el asunto y el contenido del correo que se enviará en el proceso de inscripción.</p>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Asunto</label>
                            <input type="text" class="form-control" placeholder="Bienvenido al Sistema de Gestión de Diplomados – Diplomatic">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Contenido (HTML / Texto)</label>
                            <textarea class="form-control" rows="10"
                                      placeholder="Hola {NOMBRE},&#10;&#10;Gracias por registrarte...&#10;&#10;Enlace de verificación: {LINK_VERIFICACION}"></textarea>
                            <div class="form-text small">
                                Variables sugeridas: <code>{NOMBRE}</code>, <code>{EMAIL}</code>, <code>{CLAVE_TEMPORAL}</code>, <code>{LINK_VERIFICACION}</code>
                            </div>
                        </div>

                        <div class="alert alert-light border small mb-0">
                            <div class="fw-bold mb-1">Vista previa (próximamente)</div>
                            <div class="text-muted">Cuando conectemos backend, mostraremos previsualización y validaciones.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================
         PANE: DOCUMENTOS
    ========================== -->
    <div class="tab-pane fade" id="pane-documentos" role="tabpanel" aria-labelledby="tab-documentos">
        <div class="row g-4">
            <!-- Provider (reuse same UI) -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Proveedor de correo</h5>
                        <p class="text-muted small mb-3">Puedes usar el mismo proveedor o uno diferente para Documentos.</p>

                        <div class="row g-3">
                            <?php foreach ($providers as $i => $p): ?>
                                <div class="col-12">
                                    <label class="card h-100 shadow-sm p-3 mb-0 settings-card" style="cursor:pointer;">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="settings-icon-wrapper bg-<?= $p['tone'] ?> bg-opacity-10 text-<?= $p['tone'] ?>" style="width:48px;height:48px;">
                                                <i class="bi <?= htmlspecialchars($p['icon']) ?> fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['title']) ?></h6>
                                                    <input class="form-check-input"
                                                           type="radio"
                                                           name="doc_provider"
                                                           value="<?= htmlspecialchars($p['key']) ?>"
                                                           <?= $i === 0 ? 'checked' : '' ?>
                                                           data-provider-target="doc-<?= htmlspecialchars($p['key']) ?>">
                                                </div>
                                                <p class="small text-muted mb-0"><?= htmlspecialchars($p['desc']) ?></p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-4">

                        <div id="doc-provider-panels">
                            <!-- For brevity in frontend mock, reusing same fields blocks -->
                            <div class="provider-panel" id="doc-GMAIL">
                                <h6 class="fw-bold mb-2">Configuración SMTP (Gmail)</h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">From Email</label>
                                        <input type="email" class="form-control form-control-sm" placeholder="no-reply@plataformadiplomados.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">From Name</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="Diplomatic">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">SMTP Host</label>
                                        <input type="text" class="form-control form-control-sm" value="smtp.gmail.com" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" value="587" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <input type="text" class="form-control form-control-sm" value="TLS" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario SMTP</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="no-reply@plataformadiplomados.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">App Password</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>

                            <div class="provider-panel d-none" id="doc-OUTLOOK">
                                <h6 class="fw-bold mb-2">Configuración SMTP (Outlook / Hotmail)</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">SMTP Host</label>
                                        <input type="text" class="form-control form-control-sm" value="smtp.office365.com" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" value="587" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <input type="text" class="form-control form-control-sm" value="TLS" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario SMTP</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="tu-correo@outlook.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Contraseña</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>

                            <div class="provider-panel d-none" id="doc-YAHOO">
                                <h6 class="fw-bold mb-2">Configuración SMTP (Yahoo)</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">SMTP Host</label>
                                        <input type="text" class="form-control form-control-sm" value="smtp.mail.yahoo.com" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" value="587" readonly>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <input type="text" class="form-control form-control-sm" value="TLS" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario SMTP</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="tu-correo@yahoo.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">App Password</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>

                            <div class="provider-panel d-none" id="doc-CUSTOM">
                                <h6 class="fw-bold mb-2">Configuración Personalizada</h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Protocolo</label>
                                        <select class="form-select form-select-sm">
                                            <option value="SMTP" selected>SMTP (Enviar)</option>
                                            <option value="POP3">POP3 (Recibir)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Host</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="smtp.tu-dominio.com">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Puerto</label>
                                        <input type="number" class="form-control form-control-sm" placeholder="587">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small mb-1">Seguridad</label>
                                        <select class="form-select form-select-sm">
                                            <option value="TLS" selected>TLS</option>
                                            <option value="SSL">SSL</option>
                                            <option value="NONE">Ninguna</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Usuario</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="no-reply@plataformadiplomados.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Contraseña</label>
                                        <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="small text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Luego conectamos “Guardar” y “Enviar prueba”.
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                                <i class="bi bi-send-check me-1"></i> Enviar correo de prueba
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Plantilla del correo (Documentos)</h5>
                        <p class="text-muted small mb-3">Define el asunto y el contenido del correo cuando se envíen documentos/constancias.</p>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Asunto</label>
                            <input type="text" class="form-control" placeholder="Tu documento está disponible – Diplomatic">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1">Contenido (HTML / Texto)</label>
                            <textarea class="form-control" rows="10"
                                      placeholder="Hola {NOMBRE},&#10;&#10;Adjuntamos tu documento / constancia...&#10;&#10;Equipo Diplomatic"></textarea>
                            <div class="form-text small">
                                Variables sugeridas: <code>{NOMBRE}</code>, <code>{EMAIL}</code>, <code>{NOMBRE_DOCUMENTO}</code>, <code>{LINK_DESCARGA}</code>
                            </div>
                        </div>

                        <div class="alert alert-light border small mb-0">
                            <div class="fw-bold mb-1">Vista previa (próximamente)</div>
                            <div class="text-muted">Luego agregamos previsualización, adjuntos y logs.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    function wireProviderRadios(groupNamePrefix) {
        const radios = document.querySelectorAll('input[type="radio"][name="' + groupNamePrefix + '_provider"]');
        if (!radios.length) return;

        function showPanel(targetIdPrefix) {
            const panels = document.querySelectorAll('#' + targetIdPrefix + '-provider-panels .provider-panel');
            panels.forEach(p => p.classList.add('d-none'));

            const checked = document.querySelector('input[type="radio"][name="' + groupNamePrefix + '_provider"]:checked');
            if (!checked) return;

            const target = checked.getAttribute('data-provider-target');
            const panel = document.getElementById(targetIdPrefix + '-' + checked.value);
            // fallback: use data-provider-target exact
            const panel2 = document.getElementById(target);

            (panel || panel2) && (panel || panel2).classList.remove('d-none');
        }

        radios.forEach(r => r.addEventListener('change', () => showPanel(groupNamePrefix)));
        showPanel(groupNamePrefix);
    }

    // "ins_provider" and "doc_provider"
    wireProviderRadios('ins');
    wireProviderRadios('doc');
})();
</script>
