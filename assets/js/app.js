// Shared SIRAH JavaScript for every page.
const routeParam = new URLSearchParams(window.location.search).get('page');
const pathPage = window.location.pathname.split('/').pop().replace(/\.php$/, '');
const currentPage = routeParam || (pathPage === 'index' || !pathPage ? 'dashboard' : pathPage);
if (window.lucide) window.lucide.createIcons();
function readPageData() {
    const el = document.getElementById('sirah-page-data');
    if (!el) return {};
    try { return JSON.parse(el.textContent); } catch (error) { console.error('Data halaman tidak valid.', error); return {}; }
}
// Turn legacy destructive links into CSRF-protected POST requests.
document.addEventListener('click', (event) => {
    if (event.defaultPrevented) return;
    const domTarget = event.target;
    const link = domTarget instanceof Element ? domTarget.closest('a[href]') : null;
    if (!link) return;
    const target = new URL(link.href, window.location.href);
    const isDeleteAction = [...target.searchParams.keys()].some((key) =>
        ['hapus', 'hapus_user', 'hapus_kelas', 'keluarkan_santri'].includes(key)
    ) || /proses_hapus_(santri|ustadz)\.php$/.test(target.pathname);
    if (!isDeleteAction) return;

    event.preventDefault();
    const csrf = document.querySelector('input[name="csrf_token"]')?.value;
    if (!csrf) {
        window.alert('Token keamanan tidak ditemukan. Muat ulang halaman lalu coba kembali.');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = target.pathname;
    const token = document.createElement('input');
    token.type = 'hidden'; token.name = 'csrf_token'; token.value = csrf;
    form.appendChild(token);
    target.searchParams.forEach((value, key) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = key; input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
});
const pageInitializers = {
    'kelola_santri': () => {
        // Buka / Tutup Form Tambah Santri
                function toggleFormSantri() {
                    const form = document.getElementById('formTambahContainer');
                    form.classList.toggle('hidden');
                    if (!form.classList.contains('hidden')) {
                        document.getElementById('inputNamaSantri').focus();
                    }
                }
        
                // Otomatis sarankan username ortu dari kata pertama nama santri
                function generateUsernameOrtu(nama) {
                    const inputUser = document.getElementById('inputUsernameOrtu');
                    const bersih = nama.trim().toLowerCase().replace(/[^a-z0-9\s]/g, '');
                    const kataPertama = bersih.split(/\s+/)[0] || '';
                    inputUser.value = kataPertama ? 'ortu_' + kataPertama : '';
                }
        
                // Filter Live Search & Jenis Kelamin
                function filterTabelSantri() {
                    const keyword = document.getElementById('cariSantri').value.toLowerCase();
                    const jk = document.getElementById('filterJK').value;
                    const baris = document.querySelectorAll('.baris-santri');
                    let tampil = 0;
        
                    baris.forEach((tr) => {
                        const dataSearch = tr.getAttribute('data-search');
                        const dataJk = tr.getAttribute('data-jk');
                        
                        const cocokKata = dataSearch.includes(keyword);
                        const cocokJk = (jk === 'ALL' || dataJk === jk);
        
                        if (cocokKata && cocokJk) {
                            tr.style.display = '';
                            tampil++;
                            tr.querySelector('.nomor-urut').innerText = tampil;
                        } else {
                            tr.style.display = 'none';
                        }
                    });
        
                    document.getElementById('jumlahTampil').innerText = tampil;
                }
        
                function bukaModalEdit(data) {
                    document.getElementById('edit_id_santri').value = data.id;
                    document.getElementById('edit_id_user_ortu').value = data.id_user_ortu || 0;
                    document.getElementById('edit_nama').value = data.nama || '';
                    document.getElementById('edit_nis').value = data.nis || '';
                    document.getElementById('edit_jk').value = data.jenis_kelamin || 'L';
                    document.getElementById('edit_tgl_lahir').value = data.tanggal_lahir || '';
                    document.getElementById('edit_alamat').value = data.alamat || '';
                    document.getElementById('edit_nama_ortu').value = data.nama_ortu || '';
                    document.getElementById('edit_no_hp').value = data.no_hp_ortu || '';
                    document.getElementById('edit_username_ortu').value = data.username_ortu || '';
                    document.getElementById('edit_id_halaqah').value = data.id_halaqah || '';
                    
                    document.getElementById('modalEditSantri').classList.remove('hidden');
        }
        
                function tutupModalEdit() {
                    document.getElementById('modalEditSantri').classList.add('hidden');
                }
        
        window.toggleFormSantri = toggleFormSantri;
        window.generateUsernameOrtu = generateUsernameOrtu;
        window.filterTabelSantri = filterTabelSantri;
        window.bukaModalEdit = bukaModalEdit;
        window.tutupModalEdit = tutupModalEdit;
    },
    'pr_anak': () => {
        let statusFilterAktif = 'ALL';
        
                function setFilterPR(status, btn) {
                    statusFilterAktif = status;
                    document.querySelectorAll('.tab-filter').forEach(b => {
                        b.className = 'tab-filter bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold px-4 py-2 rounded-xl transition';
                    });
                    btn.className = 'tab-filter bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl transition';
                    terapkanFilterPR();
                }
        
                function terapkanFilterPR() {
                    const keyword = document.getElementById('cariPR').value.toLowerCase();
                    document.querySelectorAll('.kartu-pr').forEach(card => {
                        const cocokStatus = (statusFilterAktif === 'ALL' || card.dataset.status === statusFilterAktif);
                        const cocokKata   = card.dataset.search.includes(keyword);
                        card.style.display = (cocokStatus && cocokKata) ? '' : 'none';
                    });
                }
        
        window.setFilterPR = setFilterPR;
        window.terapkanFilterPR = terapkanFilterPR;
    },
    'rapor_santri': () => {
        
    },
    'input_tasmi': () => {
        function isiOtomatisMateriTasmi(juz) {
                    const inp = document.getElementById('inputMateriTasmi');
                    if (juz == '30') {
                        inp.value = "Juz 30 (An-Naba' s/d An-Nas)";
                    } else if (juz) {
                        inp.value = "Al-Qur'an Juz " + juz + " (20 Pojok)";
                    }
                }
        
                function filterRiwayatTasmi() {
                    const q = document.getElementById('cariTasmi').value.toLowerCase();
                    document.querySelectorAll('.baris-tasmi').forEach(tr => {
                        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
                    });
                }
        
                const toastEl = document.getElementById('floatingPanduan');
                if (localStorage.getItem('hide_panduan_tasmi') !== '1') toastEl.classList.remove('hidden');
                function tutupNotifPanduan() { toastEl.classList.add('hidden'); localStorage.setItem('hide_panduan_tasmi', '1'); }
                function tampilkanNotifPanduan() { toastEl.classList.remove('hidden'); localStorage.removeItem('hide_panduan_tasmi'); }
        
        window.isiOtomatisMateriTasmi = isiOtomatisMateriTasmi;
        window.filterRiwayatTasmi = filterRiwayatTasmi;
        window.tutupNotifPanduan = tutupNotifPanduan;
        window.tampilkanNotifPanduan = tampilkanNotifPanduan;
    },
    'target_santri': () => {
        const mapPresetAyat = {
                    "Juz 30 (Juz Amma)": 564, "Juz 29 (Tabarak)": 431, "Juz 1": 148,
                    "QS. Al-Baqarah": 286, "QS. Al-Kahf": 110, "QS. Yasin": 83,
                    "QS. Ar-Rahman": 78, "QS. Al-Waqi'ah": 96, "QS. Al-Mulk": 30,
                    "QS. An-Naba'": 40, "QS. An-Nazi'at": 46, "QS. 'Abasa": 42
                };
        
                function cekPresetAyat(val) {
                    if (mapPresetAyat[val]) {
                        document.getElementById('total_ayat').value = mapPresetAyat[val];
                        hitungEstimasi();
                    }
                }
        
                function hitungEstimasi() {
                    const total = parseInt(document.getElementById('total_ayat').value, 10) || 0;
                    const speed = parseInt(document.getElementById('target_ayat').value, 10) || 0;
                    const tglMulai = document.getElementById('tgl_mulai').value;
        
                    if (total > 0 && speed > 0 && tglMulai) {
                        const hari = Math.ceil(total / speed);
                        const p = tglMulai.split('-');
                        const tglSelesai = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
                        tglSelesai.setDate(tglSelesai.getDate() + hari);
                        document.getElementById('preview_hari').innerText = hari + " Hari";
                        document.getElementById('preview_tanggal').innerText = tglSelesai.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                    } else {
                        document.getElementById('preview_hari').innerText = "- Hari";
                        document.getElementById('preview_tanggal').innerText = "Belum dihitung";
                    }
                }
        
                function muatKeForm(data) {
                    document.getElementById('id_santri').value = String(data.id_santri);
                    document.getElementById('target_surat').value = data.target_surat || '';
                    document.getElementById('total_ayat').value = data.total_ayat_target || '';
                    document.getElementById('target_ayat').value = data.target_ayat_per_hari || '';
                    document.getElementById('tgl_mulai').value = data.tanggal_mulai || '';
                    document.getElementById('keterangan').value = data.keterangan || '';
                    hitungEstimasi();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
        
                ['total_ayat', 'target_ayat', 'tgl_mulai', 'target_surat'].forEach(function (id) {
                    var el = document.getElementById(id);
                    ['input', 'change', 'keyup'].forEach(function (ev) {
                        el.addEventListener(ev, function () {
                            if (id === 'target_surat') cekPresetAyat(el.value); else hitungEstimasi();
                        });
                    });
                });
                hitungEstimasi();
        
                
        window.cekPresetAyat = cekPresetAyat;
        window.hitungEstimasi = hitungEstimasi;
        window.muatKeForm = muatKeForm;
    },
    'input_kehadiran': () => {
        
    },
    'profil_saya': () => {
        function togglePass(id) {
                    const el = document.getElementById(id);
                    el.type = el.type === 'password' ? 'text' : 'password';
                }
        
        window.togglePass = togglePass;
    },
    'input_ziyadah': () => {
        // Hitung otomatis rentang jumlah ayat
                function hitungRentangAyat() {
                    const awal = parseInt(document.getElementById('ayatAwal').value) || 0;
                    const akhir = parseInt(document.getElementById('ayatAkhir').value) || 0;
                    const badge = document.getElementById('badgeJumlahAyat');
        
                    if (awal > 0 && akhir >= awal) {
                        const total = akhir - awal + 1;
                        badge.innerText = total + ' Ayat Dihafal';
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
        
                // Info estimasi halaman mushaf ketika Juz & Pojok dipilih
                function updateInfoHalamanMushaf() {
                    const juz = parseInt(document.getElementById('inputJuz').value) || 0;
                    const pojokStr = document.getElementById('inputPojok').value || '';
                    const match = pojokStr.match(/\d+/);
                    const infoEl = document.getElementById('infoHalamanMushaf');
        
                    if (juz > 0 && match) {
                        const p = parseInt(match[0]);
                        if (p >= 1 && p <= 20) {
                            const halMushaf = (juz - 1) * 20 + p + 1;
                            infoEl.innerText = (juz === 1 && p === 1) ? '(Al-Fatihah + Hal 2)' : '(Hal. ' + halMushaf + ' Mushaf)';
                            return;
                        }
                    }
                    infoEl.innerText = '';
                }
        
                // Filter pencarian tabel riwayat hari ini
                function filterRiwayatHariIni() {
                    const q = document.getElementById('cariRiwayat').value.toLowerCase();
                    document.querySelectorAll('.baris-riwayat').forEach(tr => {
                        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
                    });
                }
        
                // Kontrol Floating Toast Panduan (Bisa Disilang)
                const toastEl = document.getElementById('floatingPanduan');
                if (localStorage.getItem('hide_panduan_ziyadah') !== '1') {
                    toastEl.classList.remove('hidden');
                }
        
                function tutupNotifPanduan() {
                    toastEl.classList.add('hidden');
                    localStorage.setItem('hide_panduan_ziyadah', '1');
                }
        
                function tampilkanNotifPanduan() {
                    toastEl.classList.remove('hidden');
                    localStorage.removeItem('hide_panduan_ziyadah');
                }
        
        window.hitungRentangAyat = hitungRentangAyat;
        window.updateInfoHalamanMushaf = updateInfoHalamanMushaf;
        window.filterRiwayatHariIni = filterRiwayatHariIni;
        window.tutupNotifPanduan = tutupNotifPanduan;
        window.tampilkanNotifPanduan = tampilkanNotifPanduan;
    },
    'kelola_hak_akses': () => {
        
    },
    'absensi_anak': () => {
        function filterLogAbsensi() {
                    const st = document.getElementById('filterStatus').value;
                    document.querySelectorAll('.baris-absensi').forEach(tr => {
                        tr.style.display = (st === 'ALL' || tr.dataset.status === st) ? '' : 'none';
                    });
                }
        
        window.filterLogAbsensi = filterLogAbsensi;
    },
    'input_murajaah': () => {
        // Hitung otomatis rentang jumlah ayat
                function hitungRentangAyat() {
                    const awal = parseInt(document.getElementById('ayatAwal').value) || 0;
                    const akhir = parseInt(document.getElementById('ayatAkhir').value) || 0;
                    const badge = document.getElementById('badgeJumlahAyat');
        
                    if (awal > 0 && akhir >= awal) {
                        const total = akhir - awal + 1;
                        badge.innerText = total + ' Ayat Diulang';
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
        
                // Info estimasi halaman mushaf ketika Juz & Pojok dipilih
                function updateInfoHalamanMushaf() {
                    const juz = parseInt(document.getElementById('inputJuz').value) || 0;
                    const pojokStr = document.getElementById('inputPojok').value || '';
                    const match = pojokStr.match(/\d+/);
                    const infoEl = document.getElementById('infoHalamanMushaf');
        
                    if (juz > 0 && match) {
                        const p = parseInt(match[0]);
                        if (p >= 1 && p <= 20) {
                            const halMushaf = (juz - 1) * 20 + p + 1;
                            infoEl.innerText = (juz === 1 && p === 1) ? '(Al-Fatihah + Hal 2)' : '(Hal. ' + halMushaf + ' Mushaf)';
                            return;
                        }
                    }
                    infoEl.innerText = '';
                }
        
                // Filter pencarian tabel riwayat hari ini
                function filterRiwayatHariIni() {
                    const q = document.getElementById('cariRiwayat').value.toLowerCase();
                    document.querySelectorAll('.baris-riwayat').forEach(tr => {
                        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
                    });
                }
        
                // Kontrol Floating Toast Panduan (Bisa Disilang)
                const toastEl = document.getElementById('floatingPanduan');
                if (localStorage.getItem('hide_panduan_murajaah') !== '1') {
                    toastEl.classList.remove('hidden');
                }
        
                function tutupNotifPanduan() {
                    toastEl.classList.add('hidden');
                    localStorage.setItem('hide_panduan_murajaah', '1');
                }
        
                function tampilkanNotifPanduan() {
                    toastEl.classList.remove('hidden');
                    localStorage.removeItem('hide_panduan_murajaah');
                }
        
        window.hitungRentangAyat = hitungRentangAyat;
        window.updateInfoHalamanMushaf = updateInfoHalamanMushaf;
        window.filterRiwayatHariIni = filterRiwayatHariIni;
        window.tutupNotifPanduan = tutupNotifPanduan;
        window.tampilkanNotifPanduan = tampilkanNotifPanduan;
    },
    'kelola-ustadz': () => {
        function toggleFormPengajar() {
                    const form = document.getElementById('formPengajarContainer');
                    form.classList.toggle('hidden');
                    if (!form.classList.contains('hidden')) {
                        document.getElementById('inputNamaPengajar').focus();
                    }
                }
        
                function updatePrefixPengajar() {
                    const jk = document.querySelector('input[name="jenis_kelamin"]:checked').value;
                    const passInput = document.getElementById('inputPasswordPengajar');
                    if (passInput.value === 'ustadz123' || passInput.value === 'ustadzah123') {
                        passInput.value = (jk === 'P') ? 'ustadzah123' : 'ustadz123';
                    }
                    generateUsernamePengajar();
                }
        
                function generateUsernamePengajar() {
                    const nama = document.getElementById('inputNamaPengajar').value;
                    const jk = document.querySelector('input[name="jenis_kelamin"]:checked').value;
                    const prefix = (jk === 'P') ? 'ustadzah_' : 'ustadz_';
        
                    // Hilangkan kata sapaan ustadz/ustadzah dari nama agar tidak dobel di username
                    const bersih = nama.toLowerCase()
                        .replace(/\bustadzah\b/g, '')
                        .replace(/\bustadz\b/g, '')
                        .replace(/\bust\b/g, '')
                        .replace(/[^a-z0-9\s]/g, '')
                        .trim();
        
                    const kataPertama = bersih.split(/\s+/)[0] || '';
                    document.getElementById('inputUsernamePengajar').value = kataPertama ? prefix + kataPertama : '';
                }
        
                function filterPengajar() {
                    const keyword = document.getElementById('cariPengajar').value.toLowerCase();
                    const kat = document.getElementById('filterKategori').value;
                    const baris = document.querySelectorAll('.baris-pengajar');
                    let tampil = 0;
        
                    baris.forEach(tr => {
                        const cocokKata = tr.dataset.search.includes(keyword);
                        const cocokKat  = (kat === 'ALL' || tr.dataset.jk === kat);
                        if (cocokKata && cocokKat) {
                            tr.style.display = '';
                            tampil++;
                            tr.querySelector('.nomor-urut').innerText = tampil;
                        } else {
                            tr.style.display = 'none';
                        }
                    });
                    document.getElementById('jumlahTampil').innerText = tampil;
                }
        
                function bukaModalEditPengajar(data, jk) {
                    document.getElementById('edit_id_user').value = data.id;
                    document.getElementById('edit_jk_pengajar').value = jk;
                    document.getElementById('edit_nama_pengajar').value = data.nama_lengkap || '';
                    document.getElementById('edit_lokal_pengajar').value = data.nama_halaqah || '';
                    document.getElementById('edit_username_pengajar').value = data.username || '';
                    document.getElementById('modalEditPengajar').classList.remove('hidden');
                }
        
                function tutupModalEditPengajar() {
                    document.getElementById('modalEditPengajar').classList.add('hidden');
                }
        
        window.toggleFormPengajar = toggleFormPengajar;
        window.updatePrefixPengajar = updatePrefixPengajar;
        window.generateUsernamePengajar = generateUsernamePengajar;
        window.filterPengajar = filterPengajar;
        window.bukaModalEditPengajar = bukaModalEditPengajar;
        window.tutupModalEditPengajar = tutupModalEditPengajar;
    },
    'input_pr': () => {
        function filterRiwayatPR() {
                    const q = document.getElementById('cariRiwayatPR').value.toLowerCase();
                    document.querySelectorAll('.baris-pr').forEach(tr => {
                        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
                    });
                }
        
                const toastEl = document.getElementById('floatingPanduan');
                if (localStorage.getItem('hide_panduan_pr') !== '1') {
                    toastEl.classList.remove('hidden');
                }
                function tutupNotifPanduan() {
                    toastEl.classList.add('hidden');
                    localStorage.setItem('hide_panduan_pr', '1');
                }
                function tampilkanNotifPanduan() {
                    toastEl.classList.remove('hidden');
                    localStorage.removeItem('hide_panduan_pr');
                }
        
        window.filterRiwayatPR = filterRiwayatPR;
        window.tutupNotifPanduan = tutupNotifPanduan;
        window.tampilkanNotifPanduan = tampilkanNotifPanduan;
    },
    'kelola_kelas': () => {
        let filterKetAktif = 'ALL';
        
                function filterKategoriLokal(ket, btn) {
                    filterKetAktif = ket.toLowerCase();
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    terapkanFilterLokal();
                }
        
                function terapkanFilterLokal() {
                    const cariEl = document.getElementById('cariLokal');
                    const keyword = cariEl ? cariEl.value.toLowerCase() : '';
                    let urut = 0;
                    document.querySelectorAll('.baris-lokal').forEach(tr => {
                        const ket = tr.dataset.ket.toLowerCase();
                        const cocokKet = (filterKetAktif === 'all' || ket.includes(filterKetAktif));
                        const cocokKata = tr.dataset.search.includes(keyword);
        
                        if (cocokKet && cocokKata) {
                            tr.style.display = '';
                            urut++;
                            tr.querySelector('.nomor-lokal').innerText = urut;
                        } else {
                            tr.style.display = 'none';
                        }
                    });
                }
        
                function isiNamaDariDropdown(selectEl, targetInputId) {
                    const opt = selectEl.options[selectEl.selectedIndex];
                    const nama = opt.getAttribute('data-nama');
                    if (nama) {
                        document.getElementById(targetInputId).value = nama;
                    }
                }
        
                function bukaModalTambahKelas() { document.getElementById('modalTambahKelas')?.classList.remove('hidden'); }
                function tutupModalTambahKelas() { document.getElementById('modalTambahKelas')?.classList.add('hidden'); }
        
                function bukaModalEditKelas(data) {
                    document.getElementById('edit_id_kelas').value = data.id;
                    document.getElementById('edit_nama_halaqah').value = data.nama_halaqah || '';
                    document.getElementById('edit_id_ustadz').value = data.id_ustadz || '';
                    document.getElementById('edit_nama_pengajar').value = data.nama_user_ustadz || data.nama_pengajar || '';
                    document.getElementById('edit_keterangan').value = data.keterangan || '';
                    document.getElementById('modalEditKelas')?.classList.remove('hidden');
                }
                function tutupModalEditKelas() { document.getElementById('modalEditKelas')?.classList.add('hidden'); }
        
                function bukaModalAssignSantri() { document.getElementById('modalAssignSantri')?.classList.remove('hidden'); }
                function tutupModalAssignSantri() { document.getElementById('modalAssignSantri')?.classList.add('hidden'); }
        
        window.filterKategoriLokal = filterKategoriLokal;
        window.terapkanFilterLokal = terapkanFilterLokal;
        window.isiNamaDariDropdown = isiNamaDariDropdown;
        window.bukaModalTambahKelas = bukaModalTambahKelas;
        window.tutupModalTambahKelas = tutupModalTambahKelas;
        window.bukaModalEditKelas = bukaModalEditKelas;
        window.tutupModalEditKelas = tutupModalEditKelas;
        window.bukaModalAssignSantri = bukaModalAssignSantri;
        window.tutupModalAssignSantri = tutupModalAssignSantri;
    },
    'dashboard': () => {
        const d = readPageData();
        const trend = document.getElementById('progresChart');
        if (window.Chart && trend) new Chart(trend, {
            type: 'line',
            data: { labels: d.chart_labels || [], datasets: [
                { label: 'Ziyadah (Ayat)', data: d.chart_ziyadah || [], borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', borderWidth: 2.5, tension: .4, fill: true, pointRadius: 4, pointBackgroundColor: '#10b981' },
                { label: "Muraja'ah (Kali)", data: d.chart_murajaah || [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', borderWidth: 2.5, tension: .4, fill: true, pointRadius: 4, pointBackgroundColor: '#3b82f6' }
            ] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
        });
        const grade = document.getElementById('predikatChart');
        if (window.Chart && grade && d.predikat_data) new Chart(grade, {
            type: 'doughnut',
            data: { labels: ['Predikat A','Predikat B','Predikat C'], datasets: [{ data: [d.predikat_data.A,d.predikat_data.B,d.predikat_data.C], backgroundColor: ['#10b981','#f59e0b','#f43f5e'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
        });
    },
    'input_rapor': () => {
        const surat = readPageData().surat_juz_30 || [];
        function hapusPojok(btn) { btn.closest('.pojok-item').remove(); updateNomorPojok(); }
        function tambahPojok(namaDefault = '', nilaiDefault = '') {
            const container = document.getElementById('pojok-container');
            if (!container) return;
            const div = document.createElement('div');
            div.className = 'pojok-item p-3 bg-slate-50 border border-slate-200 rounded-xl relative group transition hover:border-indigo-400 hover:bg-white';
            div.innerHTML = '<button type="button" onclick="hapusPojok(this)" class="absolute top-2 right-2 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition"><i data-lucide="x-circle" class="w-4 h-4"></i></button>' +
                '<label class="block text-[11px] font-bold text-slate-500 mb-1">Materi <span class="nomor-pojok"></span></label>' +
                '<input type="text" name="materi[]" value="' + namaDefault + '" required class="w-full px-2.5 py-1.5 mb-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 outline-none" placeholder="Nama Surat / Halaman">' +
                '<input type="number" name="nilai[]" value="' + nilaiDefault + '" min="0" max="100" placeholder="Nilai (0-100)" class="input-nilai-rapor w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-bold text-center text-indigo-700 outline-none">';
            container.appendChild(div); updateNomorPojok(); if (window.lucide) window.lucide.createIcons();
        }
        function updateNomorPojok() { document.querySelectorAll('.nomor-pojok').forEach((el,i) => el.innerText=i+1); }
        function muatPreset37Surat() { if (!confirm('Reset tampilan form menjadi 37 Surat Juz 30 standar?')) return; const c=document.getElementById('pojok-container'); if(c){c.innerHTML='';surat.forEach(s=>tambahPojok(s,''));} }
        function muatPreset20Pojok(juz) { if (!confirm('Reset tampilan form menjadi 20 Pojok standar untuk Juz '+juz+'?')) return; const c=document.getElementById('pojok-container'); if(!c)return; c.innerHTML=''; for(let i=1;i<=20;i++){const hal=(juz-1)*20+i+1;tambahPojok((juz===1&&i===1)?'Al-fatihah + Halaman 2':'Halaman '+hal,'');} }
        function isiNilaiMassal(angka) { document.querySelectorAll('.input-nilai-rapor').forEach(inp=>{if(inp.value==='')inp.value=angka;}); }
        Object.assign(window,{hapusPojok,tambahPojok,updateNomorPojok,muatPreset37Surat,muatPreset20Pojok,isiNilaiMassal});
    },
    'progres_santri': () => {
        function filterTabelProgres() { const input=document.getElementById('filterJenisProgres'); if(!input)return; const jenis=input.value; document.querySelectorAll('.baris-progres').forEach(tr=>tr.style.display=(jenis==='ALL'||tr.dataset.jenis===jenis)?'':'none'); }
        window.filterTabelProgres=filterTabelProgres;
        const d=readPageData(), canvas=document.getElementById('grafikProgres');
        if(window.Chart&&canvas&&d.chart_active)new Chart(canvas,{type:'line',data:{labels:d.chart_labels||[],datasets:[{label:'Ziyadah (Ayat)',data:d.chart_ziyadah||[],borderColor:'#0284c7',backgroundColor:'rgba(2,132,199,.1)',borderWidth:2.5,tension:.4,fill:true,pointRadius:4,pointBackgroundColor:'#0284c7'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
    },
    'monitoring_anak': () => {
        function filterRiwayat() { const input=document.getElementById('filterJenis'); if(!input)return; const jenis=input.value; document.querySelectorAll('.baris-riwayat').forEach(tr=>tr.style.display=(jenis==='ALL'||tr.dataset.jenis===jenis)?'':'none'); }
        window.filterRiwayat=filterRiwayat;
        const d=readPageData(), canvas=document.getElementById('progresAnakChart');
        if(window.Chart&&canvas&&d.chart_active)new Chart(canvas,{type:'bar',data:{labels:d.chart_labels||[],datasets:[{label:'Ziyadah (Ayat)',data:d.chart_ziyadah||[],backgroundColor:'#4f46e5',borderRadius:8,barThickness:32}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}}});
    },    'rekap_bulanan': () => {
        
    },
    'login': () => {
        function togglePassword() {
                    const input = document.getElementById('inputPassword');
                    const eyeOpen = document.getElementById('eyeOpen');
                    const eyeClosed = document.getElementById('eyeClosed');
        
                    if (input.type === 'password') {
                        input.type = 'text';
                        eyeOpen.classList.add('hidden');
                        eyeClosed.classList.remove('hidden');
                    } else {
                        input.type = 'password';
                        eyeOpen.classList.remove('hidden');
                        eyeClosed.classList.add('hidden');
                    }
                }
        
        window.togglePassword = togglePassword;
    },
};
if (pageInitializers[currentPage]) pageInitializers[currentPage]();
