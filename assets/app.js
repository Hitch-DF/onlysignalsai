// styles
import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

// jQuery + DataTables
import $ from 'jquery';
import dt from 'datatables.net-bs5';

document.addEventListener('DOMContentLoaded', () => {
	const $signalsTable = $('#signals-table');
	if ($signalsTable.length > 0) {
		const table = $signalsTable.DataTable({
			orderCellsTop: true,
			fixedHeader: true,
			pageLength: 10,
			lengthChange: false,
			language: {
				url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
			}
		});

		// Colonnes dynamiques
		document.querySelectorAll('.column-filter').forEach(el => {
			el.addEventListener('change', () => {
				const col = el.getAttribute('data-column');
				const val = el.value;
				table.column(col).search(val).draw();
			});
		});

		// Date range
		const dateInput = document.getElementById('date-range');
		if (dateInput) {
			dateInput.addEventListener('change', () => {
				table.draw();
			});

			$.fn.dataTable.ext.search.push((settings, data) => {
				const range = dateInput.value;
				if (!range.includes(' - ')) return true;

				const [start, end] = range.split(' - ').map(s => new Date(s));
				const rowDate = new Date(data[2]); // attention à l'index de colonne

				return rowDate >= start && rowDate <= end;
			});
		}

		// Reset filters
		const resetBtn = document.getElementById('reset-filters');
		if (resetBtn) {
			resetBtn.addEventListener('click', () => {
				document.querySelectorAll('.column-filter').forEach(el => el.value = '');
				if (dateInput) dateInput.value = '';
				table.search('').columns().search('').draw();
			});
		}
	}
});
