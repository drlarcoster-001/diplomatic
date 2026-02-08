<?php
/**
 * VISTA: GESTIÓN DE USUARIOS
 * Archivo: app/views/users/index.php
 */
?>

<style>
    .avatar-initials {
        width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; font-weight: bold; font-size: 14px;
    }
    .table-hover tbody tr:hover { background-color: #f1f5f9; }
    .action-icon { cursor: pointer; transition: color 0.2s; }
    .action-icon:hover { color: #0d6efd; }
</style>

<div class="container-fluid p-0">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-0">Gestión de Usuarios</h3>
            <div class="text-muted small">Administre los accesos y roles del sistema.</div>
        </div>
        <button class="btn btn-primary shadow-sm px-4" onclick="alert('Próximamente: Formulario de Registro')">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Usuario
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-2 bg-light rounded border">
            <div class="input-group input-group-lg border-0">
                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="userSearch" class="form-control bg-transparent border-0 fs-6" placeholder="Buscar por nombre, correo, rol...">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="usersTable">
                    <thead class="bg-light border-bottom">
                        <tr class="text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <th class="ps-4 py-3">Usuario</th>
                            <th>Rol Asignado</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initials bg-primary text-white me-3 shadow-sm">
                                        <?= substr($u['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= $u['name'] ?></div>
                                        <div class="text-muted small"><?= $u['email'] ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <?php 
                                    $badge = 'bg-secondary';
                                    if($u['role'] === 'ADMIN') $badge = 'bg-dark';
                                    if($u['role'] === 'ACADEMIC') $badge = 'bg-info text-dark';
                                    if($u['role'] === 'FINANCIAL') $badge = 'bg-warning text-dark';
                                ?>
                                <span class="badge <?= $badge ?> rounded-pill fw-normal px-3 py-2 border border-light shadow-sm">
                                    <?= $u['role'] ?>
                                </span>
                            </td>

                            <td>
                                <?php if($u['status'] === 'ACTIVE'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-muted small">
                                <i class="bi bi-calendar3 me-1"></i> <?= $u['created_at'] ?>
                            </td>

                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light border me-1" title="Editar">
                                        <i class="bi bi-pencil action-icon"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border text-danger" title="Eliminar">
                                        <i class="bi bi-trash action-icon"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div id="noResults" class="text-center py-5 d-none">
                    <div class="text-muted mb-2"><i class="bi bi-search display-6"></i></div>
                    <p class="text-muted mb-0">No se encontraron resultados para su búsqueda.</p>
                </div>
            </div>
        </div>
        
        <div class="card-footer bg-white border-top py-3">
            <button class="btn btn-outline-secondary btn-sm" onclick="exportToCSV()">
                <i class="bi bi-download me-2"></i>Exportar Reporte CSV
            </button>
        </div>
    </div>
</div>

<script>
    // 1. BUSCADOR EN VIVO
    document.getElementById('userSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#usersTable tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            if(text.includes(filter)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('noResults').classList.toggle('d-none', visibleCount > 0);
    });

    // 2. EXPORTAR A CSV
    function exportToCSV() {
        let csv = [];
        let rows = document.querySelectorAll("table tr");
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            // Ignorar última columna (acciones)
            for (let j = 0; j < cols.length - 1; j++) {
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                row.push('"' + data + '"');
            }
            if(rows[i].style.display !== 'none') csv.push(row.join(","));
        }

        let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        let downloadLink = document.createElement("a");
        downloadLink.download = "reporte_usuarios.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }
</script>