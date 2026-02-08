document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DEL BUSCADOR ---
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('usersTable');
    const noResults = document.getElementById('noResults');

    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                // Buscamos texto en todas las celdas de la fila
                const textContent = rows[i].textContent.toLowerCase();
                
                if (textContent.indexOf(filter) > -1) {
                    rows[i].style.display = "";
                    visibleCount++;
                } else {
                    rows[i].style.display = "none";
                }
            }

            // Mostrar mensaje si no hay resultados
            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        });
    }

    // --- LÓGICA DE EXPORTAR A CSV ---
    const btnExport = document.getElementById('btnExport');
    
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            let csv = [];
            const rows = document.querySelectorAll("table tr");
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll("td, th");
                
                // Omitimos la última columna (Acciones)
                for (let j = 0; j < cols.length - 1; j++) {
                    // Limpiamos el texto de espacios extra y saltos de linea
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    row.push('"' + data + '"'); // Comillas para evitar errores con comas
                }
                
                // Solo agregamos la fila si es visible (respeta el buscador)
                if (rows[i].style.display !== 'none') {
                    csv.push(row.join(","));
                }
            }

            downloadCSV(csv.join("\n"), 'usuarios_sistema.csv');
        });
    }

    function downloadCSV(csv, filename) {
        const csvFile = new Blob([csv], {type: "text/csv"});
        const downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }
});