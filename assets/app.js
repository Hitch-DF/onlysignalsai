// styles
import './styles/dark-theme.scss';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'jquery-ui/themes/base/all.css';

// libs
import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';
import dt from 'datatables.net-bs5';

document.addEventListener('DOMContentLoaded', () => {
	setTimeout(() => {
		const loaderIcon = document.getElementById("loaderIcon");
		loaderIcon.outerHTML = `<i class="bi bi-check-circle-fill text-success"></i>`;

		const statusText = document.getElementById("statusText");
		statusText.textContent = "Connecté";
		statusText.classList.remove("text-danger");
		statusText.classList.add("text-success");
	}, 2000);

	const lang = document.documentElement.lang || 'en';
	const langFile = lang === 'fr' ? 'fr-FR' : 'en-GB';

	// === Initialisation de la DataTable ===
	const table = $('#signals-table-history').DataTable({
		order: [[0, 'desc']],
		orderCellsTop: true,
		fixedHeader: true,
		pageLength: 20,
		lengthChange: false,
		language: {
			url: `https://cdn.datatables.net/plug-ins/1.13.4/i18n/${langFile}.json`
		}
	});

	// === FILTRE PAR DATE ===
	let startDate = null;
	let endDate = null;

	$.fn.dataTable.ext.search.push((settings, data) => {
		const createdAt = new Date(data[0]);
		if (!startDate && !endDate) return true;
		if (startDate && !endDate) return createdAt >= startDate;
		if (!startDate && endDate) return createdAt <= endDate;
		return createdAt >= startDate && createdAt <= endDate;
	});

	$('#min-date, #max-date').datepicker({
		dateFormat: 'yy-mm-dd',
		onSelect: function () {
			startDate = $('#min-date').datepicker('getDate');
			endDate = $('#max-date').datepicker('getDate');
			table.draw();
		}
	});

	// === FILTRE PAR COLONNE ===
	$('.column-filter').on('change', function () {
		const columnIndex = $(this).data('column');
		const value = $(this).val();
		table.column(columnIndex).search(value).draw();
	});

	// === RÉINITIALISATION DES FILTRES ===
	$('#reset-filters').on('click', () => {
		startDate = null;
		endDate = null;
		$('#min-date, #max-date').val('');

		$('.column-filter').each(function () {
			$(this).val('');
			const columnIndex = $(this).data('column');
			table.column(columnIndex).search('');
		});

		table.draw();
	});

	// Screenshot modal handler
	const screenshotModal = document.getElementById('screenshotModal');
	const screenshotImage = document.getElementById('modal-screenshot-image');
	const placeholder = document.getElementById('modal-screenshot-placeholder');
	const openImageBtn = document.getElementById('open-image-tab');

	screenshotModal.addEventListener('show.bs.modal', (event) => {
		const button = event.relatedTarget;
		const imgUrl = button.getAttribute('data-img');
		const symbol = button.getAttribute('data-symbol');

		const title = screenshotModal.querySelector('.modal-title');
		title.innerHTML = `<i class="bi bi-image me-2"></i> Screenshot - ${symbol}`;

		if (imgUrl) {
			screenshotImage.src = imgUrl;
			screenshotImage.classList.remove('d-none');
			placeholder.classList.add('d-none');
			openImageBtn.href = imgUrl;
			openImageBtn.classList.remove('d-none');
		} else {
			screenshotImage.src = '';
			screenshotImage.classList.add('d-none');
			placeholder.classList.remove('d-none');
			placeholder.innerText = 'Aucun screenshot disponible.';
			openImageBtn.href = '#';
			openImageBtn.classList.add('d-none');
		}
	});
});
