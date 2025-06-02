import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

import $ from 'jquery';
import dt from 'datatables.net-bs5';

document.addEventListener('DOMContentLoaded', function () {
    const table = $('#signals-table').DataTable({
        orderCellsTop: true,
        fixedHeader: true,
        pageLength: 10,
        lengthChange: false
    });

    $('#signals-table thead tr:eq(1) th').each(function (i) {
        const input = $(this).find('input, select');
        if (input.length) {
            input.on('keyup change', function () {
                if (table.column(i).search() !== this.value) {
                    table.column(i).search(this.value).draw();
                }
            });
        }
    });

    const sidebar = document.getElementById("sidebar");
        const toggleBtn = document.getElementById("toggleSidebar");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
        });
});
