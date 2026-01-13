<?php
session_start();
// Cek apakah user sudah login
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($_SESSION['user_role'] == 'admin');
?>
<!DOCTYPE html>
<html lang="id">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Admin Antrian PTSP</title>
		<link rel="stylesheet" href="style.css">
		<style>
			/* CSS Layout Unboxed & Compact */
			body {
				background-color: #ffffff;
				margin: 0;
				padding: 0;
				height: 100vh;
				overflow: hidden;
				font-family: sans-serif;
			}
			
			.container {
				max-width: none;
				margin: 0;
				padding: 10px 20px;
				box-shadow: none;
				border-radius: 0;
				height: 100%;
				display: flex;
				flex-direction: column;
				box-sizing: border-box;
			}
			
			/* HEADER */
			.header {
				background-color: var(--kemenag-green);
				color: var(--text-light);
				border-radius: 8px;
				padding: 10px 15px;
				margin-bottom: 10px;
				position: relative;
				flex-shrink: 0;
				display: flex;
				align-items: center;
			}
			.header img { height: 50px; margin-right: 15px; }
			.header h1 { font-size: 1.4rem; margin: 0; }
			.header p { font-size: 0.9rem; margin: 0; opacity: 0.9; }
			
			.header-actions { position: absolute; top: 12px; right: 15px; display: flex; gap: 10px; }
			.btn-header {
				padding: 6px 10px; border: 1px solid white; background: transparent;
				color: white; border-radius: 4px; text-decoration: none; font-size: 0.85rem; cursor: pointer;
			}
			.btn-header:hover { background: rgba(255,255,255,0.2); }

			/* MAIN CONTENT */
			.admin-main-controls {
				padding: 0;
				flex-grow: 1;
				display: flex;
				flex-direction: column;
				min-height: 0;
				overflow-y: auto; /* Scroll jika layar terlalu kecil */
			}
			
			/* INPUT LOKET */
			.main-header-controls {
				display: flex; justify-content: space-between; align-items: center;
				padding: 8px 15px; background: #f9f9f9; border: 1px solid #eee;
				border-radius: 5px; margin-bottom: 10px; flex-shrink: 0;
			}
			.loket-input label { font-size: 1rem; font-weight: bold; margin-right: 10px; }
			.loket-input input { font-size: 1rem; padding: 5px; width: 60px; text-align: center; }
			#status-message { font-size: 0.9rem; font-weight: bold; text-align: right; flex-grow: 1; margin-left: 20px; }
			
			/* GRID KOTAK */
			.call-controls-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 10px;
				flex-grow: 1; /* Isi ruang kosong */
				margin-bottom: 10px;
				min-height: 200px;
			}
			
			.call-box-admin {
				border: 2px solid var(--kemenag-green); border-radius: 8px;
				background: #fff; text-align: center; padding: 10px;
				display: flex; flex-direction: column; justify-content: center;
				box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			}
			.call-box-admin h3 { font-size: 1.2rem; margin: 0 0 10px 0; color: var(--kemenag-green); }
			.call-box-admin .status-group {
				display: flex; justify-content: space-around; margin-bottom: 10px; 
				background: #f4f4f4; padding: 5px; border-radius: 5px;
			}
			.call-box-admin .status-item p { font-size: 0.75rem; color: #555; font-weight: bold; margin: 0; }
			.call-box-admin .status-item .status-nomor { font-size: 1.5rem; font-weight: bold; color: #333; line-height: 1.2; }
			.call-box-admin .btn { width: 100%; font-size: 1rem; padding: 10px; cursor: pointer; box-sizing: border-box; }
			.call-box-admin .btn:disabled { background-color: #aaa; cursor: not-allowed; }
			
			/* SETTINGS AREA */
			.settings-wrapper { display: flex; gap: 10px; flex-shrink: 0; margin-bottom: 10px; height: auto; min-height: 120px; }
			.setting-col { flex: 1; display: flex; flex-direction: column; gap: 10px; }
			
			.running-text-settings {
				padding: 10px 15px; background: #fcfcfc; border: 1px solid #eee; border-radius: 5px;
				display: flex; flex-direction: column; height: 100%; box-sizing: border-box;
			}
			.running-text-settings h3 { margin: 0 0 8px 0; font-size: 0.9rem; color: #333; }
			
			.input-group { display: flex; gap: 5px; width: 100%; }
			.input-group input { flex-grow: 1; font-size: 0.9rem; padding: 5px; border: 1px solid #ccc; border-radius: 3px; }
			.input-group textarea { flex-grow: 1; font-size: 0.9rem; padding: 5px; height: 60px; resize: none; font-family: sans-serif; border: 1px solid #ccc; border-radius: 3px; }
			
			.running-text-settings .btn-simpan { 
				background-color: #007bff; font-size: 0.9rem; padding: 5px 10px; 
				color: white; border: none; border-radius: 3px; cursor: pointer; align-self: flex-end; margin-top: 5px;
			}
			.status-setting-msg { font-size: 0.8rem; margin-top: 2px; height: 15px; }

			/* FOOTER */
			.admin-footer { margin: 0; padding: 0; border-top: none; text-align: center; flex-shrink: 0; }
			.btn-reset { background-color: #d9534f; padding: 10px 20px; width: 100%; font-size: 1rem; color: white; border: none; border-radius: 5px; cursor: pointer; }
			.btn-reset:hover { background-color: #c9302c; }
		</style>
	</head>
	<body>
		<div class="container">
			<header class="header">
				<div class="header-actions">
					<?php if($isAdmin): ?>
						<a href="report.php" class="btn-header">Laporan</a>
						<a href="tv.html" target="_blank" class="btn-header" style="background: #f0a900; border-color: #f0a900;">Layar TV</a>
					<?php endif; ?>
					<a href="logout.php" class="btn-header" style="background: #d9534f; border-color: #d9534f;">Logout</a>
				</div>
				<img src="https://upload.wikimedia.org/wikipedia/commons/9/9a/Kementerian_Agama_new_logo.png" alt="Logo Kemenag">
				<div>
					<h1>Admin Antrian PTSP</h1>
					<p>Halo, <b><?= strtoupper($_SESSION['user_role']) ?></b></p>
				</div>
			</header>

			<main class="admin-main-controls">
				<div class="main-header-controls">
					<div class="loket-input">
						<label for="loket-id">Nomor Loket:</label>
						<input type="number" id="loket-id" value="1" min="1">
					</div>
					<div id="status-message" style="color: orange;">Menghubungkan...</div>
				</div>

				<div class="call-controls-grid">
					<div class="call-box-admin">
						<h3>Pelayanan Haji (A)</h3>
						<div class="status-group">
							<div class="status-item"><p>Sisa</p><span id="sisa-A" class="status-nomor">0</span></div>
							<div class="status-item"><p>Dipanggil</p><span id="dipanggil-A" class="status-nomor">A-0</span></div>
						</div>
						<button class="btn btn-panggil" data-kode="A" disabled>Panggil (A)</button>
					</div>

					<div class="call-box-admin">
						<h3>Pelayanan Umrah (B)</h3>
						<div class="status-group">
							<div class="status-item"><p>Sisa</p><span id="sisa-B" class="status-nomor">0</span></div>
							<div class="status-item"><p>Dipanggil</p><span id="dipanggil-B" class="status-nomor">B-0</span></div>
						</div>
						<button class="btn btn-panggil" data-kode="B" disabled>Panggil (B)</button>
					</div>

					<div class="call-box-admin">
						<h3>Administratif (C)</h3>
						<div class="status-group">
							<div class="status-item"><p>Sisa</p><span id="sisa-C" class="status-nomor">0</span></div>
							<div class="status-item"><p>Dipanggil</p><span id="dipanggil-C" class="status-nomor">C-0</span></div>
						</div>
						<button class="btn btn-panggil" data-kode="C" disabled>Panggil (C)</button>
					</div>

					<div class="call-box-admin">
						<h3>Customer Service (D)</h3>
						<div class="status-group">
							<div class="status-item"><p>Sisa</p><span id="sisa-D" class="status-nomor">0</span></div>
							<div class="status-item"><p>Dipanggil</p><span id="dipanggil-D" class="status-nomor">D-0</span></div>
						</div>
						<button class="btn btn-panggil" data-kode="D" disabled>Panggil (D)</button>
					</div>
				</div>
				
				<?php if($isAdmin): ?>
				<div class="settings-wrapper">
					<div class="setting-col">
						<div class="running-text-settings">
							<h3>Teks Berjalan (Footer)</h3>
							<div class="input-group">
								<input type="text" id="running-text-input" placeholder="Teks footer...">
							</div>
							<button id="btn-save-text" class="btn-simpan">Simpan</button>
							<div id="status-text-message" class="status-setting-msg"></div>
						</div>
					</div>

					<div class="setting-col">
						<div class="running-text-settings" style="flex-grow: 0; height: auto;">
							<h3>YouTube Playlist ID</h3>
							<div class="input-group">
								<input type="text" id="youtube-id-input" placeholder="Contoh: PLsW_XF...">
							</div>
							<button id="btn-save-youtube" class="btn-simpan">Simpan</button>
							<div id="status-youtube-message" class="status-setting-msg"></div>
						</div>
						
						<div class="running-text-settings" style="flex-grow: 1;">
							<h3>Jadwal Kegiatan (TV)</h3>
							<div class="input-group">
								<textarea id="schedule-input" placeholder="08:00 - Buka..."></textarea>
							</div>
							<button id="btn-save-schedule" class="btn-simpan">Simpan</button>
							<div id="status-schedule-message" class="status-setting-msg"></div>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</main>
			
			<?php if($isAdmin): ?>
			<footer class="admin-footer">
				<button id="btn-reset" class="btn btn-reset">
					Reset Antrian Harian (Mulai dari 0)
				</button>
			</footer>
			<?php endif; ?>
		</div>

		<script>
			const API_URL_BASE = 'api.php'; // URL RELATIF

			const inputLoket = document.getElementById('loket-id');
			const statusMsg = document.getElementById('status-message');
			const buttonsPanggil = document.querySelectorAll('.btn-panggil');
			
			const btnReset = document.getElementById('btn-reset');
			const textInput = document.getElementById('running-text-input');
			const btnSaveText = document.getElementById('btn-save-text');
			const statusTextMsg = document.getElementById('status-text-message');
			
			const youtubeInput = document.getElementById('youtube-id-input');
			const btnSaveYoutube = document.getElementById('btn-save-youtube');
			const statusYoutubeMsg = document.getElementById('status-youtube-message');

			const scheduleInput = document.getElementById('schedule-input');
			const btnSaveSchedule = document.getElementById('btn-save-schedule');
			const statusScheduleMsg = document.getElementById('status-schedule-message');

			const sisaElements = { 'A': document.getElementById('sisa-A'), 'B': document.getElementById('sisa-B'), 'C': document.getElementById('sisa-C'), 'D': document.getElementById('sisa-D') };
			const dipanggilElements = { 'A': document.getElementById('dipanggil-A'), 'B': document.getElementById('dipanggil-B'), 'C': document.getElementById('dipanggil-C'), 'D': document.getElementById('dipanggil-D') };
			const panggilButtons = { 'A': document.querySelector('[data-kode="A"]'), 'B': document.querySelector('[data-kode="B"]'), 'C': document.querySelector('[data-kode="C"]'), 'D': document.querySelector('[data-kode="D"]') };

			async function cekStatusAdmin() {
				try {
					const response = await fetch(`${API_URL_BASE}?action=status`);
					if (!response.ok) throw new Error('Gagal terhubung ke server');
					const data = await response.json();
					for (const kode in data) {
						if (data.hasOwnProperty(kode)) {
							const antrian = data[kode];
							const sisa = antrian.total_antrian - antrian.nomor_sekarang;
							if (sisaElements[kode]) sisaElements[kode].textContent = sisa;
							if (dipanggilElements[kode]) dipanggilElements[kode].textContent = `${kode}-${antrian.nomor_sekarang}`;
							
							if (panggilButtons[kode]) {
								const btn = panggilButtons[kode];
								// HANYA update tombol jika TIDAK sedang memanggil (untuk mencegah kedip/reset prematur)
								// ATAU jika tombol sudah direset manual di finally block panggilNomor
								if (!btn.textContent.includes('Memanggil')) {
									if (sisa > 0) {
										btn.disabled = false; 
										btn.textContent = `Panggil (${kode})`;
									} else {
										btn.disabled = true; 
										btn.textContent = `Panggil (${kode})`;
									}
								}
							}
						}
					}
					if(statusMsg.style.color !== 'green') { 
						statusMsg.textContent = 'Terhubung'; statusMsg.style.color = 'green'; 
					}
				} catch (error) { 
					statusMsg.textContent = `Gagal: ${error.message}`; statusMsg.style.color = 'red'; 
				}
			}

			async function panggilNomor(kode) {
				const loket = inputLoket.value;
				if (!loket) { alert('Harap isi nomor loket'); return; }
				
				const button = panggilButtons[kode];
				button.disabled = true; 
				button.textContent = 'Memanggil...';
				
				try {
					const response = await fetch(`${API_URL_BASE}?action=panggil`, {
						method: 'POST', headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ kode: kode, loket: parseInt(loket) })
					});
					const data = await response.json();
					if (!response.ok) throw new Error(data.message);
					statusMsg.textContent = `Berhasil memanggil Nomor ${data.nomor_dipanggil}.`;
					statusMsg.style.color = 'green';
				} catch (error) { 
					statusMsg.textContent = `Error: ${error.message}`; statusMsg.style.color = 'red'; 
				} finally { 
					// --- PERBAIKAN PENTING ---
					// Kembalikan teks tombol ke default agar cekStatusAdmin bisa memprosesnya lagi
					button.textContent = `Panggil (${kode})`;
					// Biarkan cekStatusAdmin memutuskan apakah disabled atau tidak berdasarkan sisa antrian
					cekStatusAdmin(); 
				}
			}

			buttonsPanggil.forEach(button => { button.addEventListener('click', () => panggilNomor(button.dataset.kode)); });

			if (btnReset) {
				btnReset.addEventListener('click', async () => {
					if (!confirm("Reset antrian harian ke 0? Data log TIDAK dihapus.")) return;
					try {
						await fetch(`${API_URL_BASE}?action=reset`, { method: 'POST' });
						alert("Berhasil direset.");
					} catch (error) { alert(`Error: ${error.message}`); } finally { cekStatusAdmin(); }
				});
			}

			// --- FUNGSI SETTINGS (LOAD & SAVE) ---
			async function loadSetting(key, inputEl) {
				try {
					const response = await fetch(`${API_URL_BASE}?action=get_setting&key=${key}`);
					if (response.ok) {
						const data = await response.json();
						inputEl.value = data.key_value;
					}
				} catch (e) {}
			}

			async function saveSetting(key, val, btn, msgEl) {
				const originalText = btn.textContent;
				btn.textContent = '...'; btn.disabled = true;
				try {
					const response = await fetch(`${API_URL_BASE}?action=update_setting`, {
						method: 'POST', headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ key: key, value: val })
					});
					const data = await response.json();
					if (!response.ok) throw new Error(data.message);
					msgEl.textContent = "Disimpan"; msgEl.style.color = 'green';
					setTimeout(() => { msgEl.textContent = ''; }, 3000);
				} catch (error) {
					msgEl.textContent = error.message; msgEl.style.color = 'red';
				} finally {
					btn.textContent = originalText; btn.disabled = false;
				}
			}

			// Init Settings
			if (textInput && btnSaveText) {
				loadSetting('running_text', textInput);
				btnSaveText.addEventListener('click', () => saveSetting('running_text', textInput.value, btnSaveText, statusTextMsg));
			}
			if (youtubeInput && btnSaveYoutube) {
				loadSetting('youtube_playlist_id', youtubeInput);
				btnSaveYoutube.addEventListener('click', () => saveSetting('youtube_playlist_id', youtubeInput.value, btnSaveYoutube, statusYoutubeMsg));
			}
			if (scheduleInput && btnSaveSchedule) {
				loadSetting('office_schedule', scheduleInput);
				btnSaveSchedule.addEventListener('click', () => saveSetting('office_schedule', scheduleInput.value, btnSaveSchedule, statusScheduleMsg));
			}

			document.addEventListener('DOMContentLoaded', () => {
				cekStatusAdmin();
				setInterval(cekStatusAdmin, 3000); 
			});
		</script>
	</body>
</html>