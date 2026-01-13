<?php
session_start();
// Cek login dan role. Hanya admin yang boleh masuk.
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Laporan Antrian - PTSP Kemenag</title>
		<link rel="stylesheet" href="style.css">
		<style>
			/* Layout Unboxed */
			body { background-color: #ffffff; }
			.container {
				max-width: none; margin: 0; padding: 15px; 
				box-shadow: none; border-radius: 0; min-height: 100vh;
			}
			.header {
				background-color: var(--kemenag-green); color: var(--text-light);
				border-radius: 8px; margin-bottom: 15px; padding: 15px; position: relative;
			}
			/* Tombol Header */
			.header-actions { position: absolute; top: 15px; right: 15px; }
			.btn-header {
				padding: 8px 12px; border: 1px solid white; background: transparent;
				color: white; border-radius: 4px; text-decoration: none; font-size: 0.9rem;
			}
			.btn-header:hover { background: rgba(255,255,255,0.2); }

			/* Filter & Table Styles */
			.filter-controls {
				padding: 20px; background-color: #f9f9f9; border-radius: 8px;
				display: flex; gap: 15px; align-items: center; flex-wrap: wrap;
			}
			.filter-group { display: flex; flex-direction: column; }
			.button-group-right { margin-left: auto; display: flex; gap: 10px; align-items: center; }
			.filter-group label { font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; }
			.filter-group select, .filter-group input { padding: 8px; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; }
			.btn-warning { background-color: #f0ad4e; } .btn-warning:hover { background-color: #ec971f; }
			
			.laporan-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
			.laporan-table th, .laporan-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
			.laporan-table th { background-color: #f2f2f2; color: #333; }
			.laporan-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
			#loading-indicator { font-weight: bold; color: var(--kemenag-green); padding: 20px; text-align: center; font-size: 1.2rem; }
			.btn-hapus { background-color: #d9534f; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
			.btn-hapus:hover { background-color: #c9302c; }
			.status-belum { color: #888; font-style: italic; }
		</style>
	</head>
	<body>
		<div class="container">
			<header class="header">
				<div class="header-actions">
					<a href="admin.php" class="btn-header">Kembali ke Panel Admin</a>
				</div>
				<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/Kementerian_Agama_new_logo.png/150px-Kementerian_Agama_new_logo.png" alt="Logo Kemenag">
				<div>
					<h1>Laporan Antrian PTSP</h1>
					<p>Kantor Kementerian Agama Kota Depok</p>
				</div>
			</header>

			<div class="filter-controls">
				<div class="filter-group">
					<label for="filter-type">Jenis Filter:</label>
					<select id="filter-type">
						<option value="hari" selected>Harian</option>
						<option value="bulan">Bulanan</option>
						<option value="tahun">Tahunan</option>
					</select>
				</div>
				<div class="filter-group" id="group-day">
					<label for="filter-day">Pilih Tanggal:</label>
					<input type="date" id="filter-day">
				</div>
				<div class="filter-group" id="group-month" style="display:none;">
					<label for="filter-month">Pilih Bulan:</label>
					<input type="month" id="filter-month">
				</div>
				<div class="filter-group" id="group-year" style="display:none;">
					<label for="filter-year">Masukkan Tahun:</label>
					<input type="number" id="filter-year" placeholder="Contoh: 2025">
				</div>
				<div class="filter-group" style="margin-top: 20px;">
					<button id="btn-filter" class="btn">Tampilkan Laporan</button>
				</div>
				
				<div class="button-group-right">
					<div class="filter-group" style="margin-top: 20px;">
						<button id="btn-reset-waktu" class="btn btn-warning">Reset Waktu Layanan</button>
					</div>
					<div class="filter-group" style="margin-top: 20px;">
						<button id="btn-hapus-semua" class="btn btn-reset">Hapus Semua Data Log</button>
					</div>
				</div>
			</div>

			<div style="padding: 20px 0;">
				<h3 id="laporan-title">Laporan Hari Ini</h3>
				<p id="total-antrian">Total Pengantri: 0</p>
				<table class="laporan-table">
					<thead>
						<tr>
							<th>No.</th> <th>Nomor Antrian</th> <th>Layanan</th>
							<th>Waktu Ambil</th> <th>Waktu Dilayani</th> <th>Aksi</th>
						</tr>
					</thead>
					<tbody id="laporan-body"></tbody>
				</table>
				<div id="loading-indicator" style="display:none;">Memuat data...</div>
			</div>
		</div>

		<script>
			// (JavaScript TIDAK BERUBAH dari report.html sebelumnya)
			const API_URL_BASE = 'https://ptsp.amal.or.id/api.php';
			const LAYANAN_MAP = { 'A': 'Pelayanan Haji', 'B': 'Pelayanan Umrah', 'C': 'Administratif', 'D': 'Customer Service' };
			const filterType = document.getElementById('filter-type');
			const groupDay = document.getElementById('group-day');
			const groupMonth = document.getElementById('group-month');
			const groupYear = document.getElementById('group-year');
			const inputDay = document.getElementById('filter-day');
			const inputMonth = document.getElementById('filter-month');
			const inputYear = document.getElementById('filter-year');
			const btnFilter = document.getElementById('btn-filter');
			const tableBody = document.getElementById('laporan-body');
			const loading = document.getElementById('loading-indicator');
			const laporanTitle = document.getElementById('laporan-title');
			const totalAntrian = document.getElementById('total-antrian');
			const btnResetWaktu = document.getElementById('btn-reset-waktu');
			const btnHapusSemua = document.getElementById('btn-hapus-semua');
			
			function setTodayDate() {
				const today = new Date().toLocaleDateString('en-CA');
				inputDay.value = today;
				inputYear.value = new Date().getFullYear();
				inputMonth.value = new Date().toISOString().slice(0, 7);
			}
			filterType.addEventListener('change', () => {
				groupDay.style.display = 'none'; groupMonth.style.display = 'none'; groupYear.style.display = 'none';
				if (filterType.value === 'hari') groupDay.style.display = 'block';
				if (filterType.value === 'bulan') groupMonth.style.display = 'block';
				if (filterType.value === 'tahun') groupYear.style.display = 'block';
			});
			function renderTable(data) {
				tableBody.innerHTML = ''; 
				if (data.length === 0) {
					tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Tidak ada data untuk periode ini.</td></tr>'; return;
				}
				data.forEach((item, index) => {
					const tr = document.createElement('tr');
					const nomorUrut = index + 1;
					const nomorAntrian = `${item.kode_layanan}-${item.nomor_antrian}`;
					const namaLayanan = LAYANAN_MAP[item.kode_layanan] || 'N/A';
					const dtAmbil = new Date(item.waktu_ambil);
					const waktuAmbilFormatted = dtAmbil.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
					const tglAmbilFormatted = dtAmbil.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
					let waktuDilayaniFormatted = '<span class="status-belum">Belum dilayani</span>';
					if (item.waktu_dilayani) {
						const dtDilayani = new Date(item.waktu_dilayani);
						waktuDilayaniFormatted = dtDilayani.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
					}
					const tombolHapus = `<button class="btn-hapus" data-id="${item.id}">Hapus</button>`;
					tr.innerHTML = `<td>${nomorUrut}</td><td><b>${nomorAntrian}</b></td><td>${namaLayanan}</td><td>${tglAmbilFormatted}, ${waktuAmbilFormatted} WIB</td><td>${waktuDilayaniFormatted}</td><td>${tombolHapus}</td>`;
					tableBody.appendChild(tr);
				});
			}
			async function loadLaporan(params = '') {
				loading.style.display = 'block'; tableBody.innerHTML = ''; totalAntrian.textContent = 'Total Pengantri: 0';
				try {
					const response = await fetch(`${API_URL_BASE}?action=laporan${params}`);
					if (!response.ok) throw new Error('Gagal ambil data');
					const data = await response.json();
					totalAntrian.textContent = `Total Pengantri: ${data.length}`;
					renderTable(data);
				} catch (error) {
					tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red;">Error: ${error.message}</td></tr>`;
				} finally { loading.style.display = 'none'; }
			}
			btnFilter.addEventListener('click', () => {
				let params = ''; let title = 'Laporan';
				if (filterType.value === 'hari' && inputDay.value) params = `&day=${inputDay.value}`;
				else if (filterType.value === 'bulan' && inputMonth.value) params = `&month=${inputMonth.value}`;
				else if (filterType.value === 'tahun' && inputYear.value) params = `&year=${inputYear.value}`;
				loadLaporan(params);
			});
			async function hapusLog(id) {
				try {
					const response = await fetch(`${API_URL_BASE}?action=hapus`, {
						method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id })
					});
					const data = await response.json();
					if (data.success) { alert(data.message); btnFilter.click(); } else { alert(data.message); }
				} catch (e) { alert(e.message); }
			}
			tableBody.addEventListener('click', (e) => {
				if (e.target.classList.contains('btn-hapus')) {
					if(confirm('Hapus permanen?')) hapusLog(e.target.dataset.id);
				}
			});
			btnResetWaktu.addEventListener('click', async () => {
				if(!confirm("Yakin reset Waktu Layanan harian?")) return;
				try {
					await fetch(`${API_URL_BASE}?action=reset`, { method: 'POST' });
					alert("Waktu layanan direset."); btnFilter.click();
				} catch(e) { alert(e.message); }
			});
			btnHapusSemua.addEventListener('click', async () => {
				if(!confirm("Yakin HAPUS SEMUA DATA LOG? Ini permanen.")) return;
				try {
					await fetch(`${API_URL_BASE}?action=hapus_semua`, { method: 'POST' });
					alert("Semua data dihapus."); btnFilter.click();
				} catch(e) { alert(e.message); }
			});
			document.addEventListener('DOMContentLoaded', () => { setTodayDate(); loadLaporan(`&day=${inputDay.value}`); });
		</script>
	</body>
</html>