// styles
import './styles/dark-theme.scss';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

// jQuery + DataTables
import $ from 'jquery';
import dt from 'datatables.net-bs5';

import 'litepicker';
import 'litepicker/dist/css/litepicker.css';


document.addEventListener('DOMContentLoaded', () => {
	const $signalsTable = $('#signals-table');
	const $signalsHistoryTable = $('#signals-table-history');
	const $usersTable = $('#users-table');

	const dateInput = document.getElementById('date-range');

	// Initialize Litepicker
	if (dateInput) {
		new Litepicker({
			element: dateInput,
			singleMode: false,
			autoApply: true,
			lang: 'fr-FR',
			numberOfMonths: 2,
			numberOfColumns: 2,
			format: 'YYYY-MM-DD',
			dropdowns: {
				minYear: 2023,
				maxYear: null,
				months: true,
				years: true
			},
		});

		$.fn.dataTable.ext.search.push((settings, data) => {
			const range = dateInput.value;
			if (!range.includes(' - ')) return true;

			const [start, end] = range.split(' - ').map(d => new Date(d));
			const rowDate = new Date(data[2]); // Date = index 2, adjust if needed

			return rowDate >= start && rowDate <= end;
		});

		dateInput.addEventListener('change', () => {
			$signalsTable.DataTable().draw();
			$signalsHistoryTable.DataTable().draw();
		});
	}

	const initTable = ($table) => {
		if ($table.length > 0) {
			const table = $table.DataTable({
				orderCellsTop: true,
				fixedHeader: true,
				pageLength: 10,
				lengthChange: false,
				language: {
					url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
				}
			});

			document.querySelectorAll('.column-filter').forEach(el => {
				el.addEventListener('change', () => {
					const col = el.getAttribute('data-column');
					table.column(col).search(el.value).draw();
				});
			});

			const resetBtn = document.getElementById('reset-filters');
			if (resetBtn) {
				resetBtn.addEventListener('click', () => {
					document.querySelectorAll('.column-filter').forEach(el => el.value = '');
					if (dateInput) dateInput.value = '';
					table.search('').columns().search('').draw();
				});
			}
		}
	};

	initTable($signalsTable);
	initTable($signalsHistoryTable);
	initTable($usersTable);
});
