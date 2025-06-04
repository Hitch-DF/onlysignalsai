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

    // Colonnes dynamiques (assets, types, categories)
    document.querySelectorAll('.column-filter').forEach(el => {
        el.addEventListener('change', () => {
            const col = el.getAttribute('data-column');
            const val = el.value;
            table.column(col).search(val).draw();
        });
    });

    // Filtre date range
    const dateInput = document.getElementById('date-range');
    dateInput.addEventListener('change', function () {
        table.draw();
    });

    // Reset filters
    document.getElementById('reset-filters').addEventListener('click', () => {
        document.querySelectorAll('.column-filter').forEach(el => el.value = '');
        dateInput.value = '';
        table.search('').columns().search('').draw();
    });

    // Custom filter for date range
    $.fn.dataTable.ext.search.push(function (settings, data) {
        const range = dateInput.value;
        if (!range.includes(' - ')) return true;

        const [start, end] = range.split(' - ').map(s => new Date(s));
        const date = new Date(data[2]);

        return date >= start && date <= end;
    });
});
