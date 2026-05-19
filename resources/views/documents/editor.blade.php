<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->title }} - Editor Kolaboratif</title>
    <!-- Quill Snow Theme CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .ql-container { font-family: 'Inter', sans-serif; font-size: 1rem; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; border-color: #cbd5e1 !important; }
        .ql-toolbar { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; border-color: #cbd5e1 !important; background-color: #f8fafc; }
        .ql-editor { min-height: 500px; padding: 2rem; background: white; }
        /* Custom cursor styling for Quill Cursors */
        .ql-cursor .ql-cursor-flag { padding: 2px 6px; border-radius: 4px; color: white; font-size: 11px; font-weight: 600; white-space: nowrap; box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
        .ql-cursor .ql-cursor-caret { width: 2px; }
    </style>
    <meta name="document-id" content="{{ $document->id }}">
    <meta name="current-user-name" content="{{ session('user_name', 'Anonymous') }}">
    <meta name="current-user-id" content="{{ session('user_id') }}">
</head>
<body class="h-full flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold text-slate-800 flex items-center">
                    {{ $document->title }}
                </h1>
            </div>

            <div class="flex items-center space-x-4">
                <div class="text-sm text-slate-600 flex items-center space-x-2">
                    <span>Halo, <strong class="text-indigo-600 font-semibold" id="display-user-name">{{ session('user_name', 'Anonymous') }}</strong></span>
                    <button onclick="openNameModal()" class="text-xs text-slate-400 hover:text-indigo-600 underline cursor-pointer">(Ganti Nama)</button>
                </div>
                
                <button id="save-revision-btn" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-150 cursor-pointer disabled:opacity-50">
                    <svg class="w-4 h-4 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Simpan Revisi
                </button>
            </div>
        </div>
    </header>

    @if(session('success'))
    <div class="bg-emerald-50 border-b border-emerald-200 p-4 text-center text-sm text-emerald-800" id="success-alert">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-indigo-600 text-white px-4 py-2 text-center text-sm hidden shadow-inner transition-all" id="saving-alert">
        <span class="inline-block animate-spin mr-2">⏳</span> Menyimpan revisi ke database...
    </div>

    <!-- Main Workspace kotak tengah -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-8">
        
        <!-- Left: Rich Text Editor -->
        <div class="flex-1 flex flex-col shadow-sm rounded-xl border border-slate-200 bg-white">
            <div id="editor-toolbar">
                <span class="ql-formats">
                    <select class="ql-header">
                        <option value="1">Heading 1</option>
                        <option value="2">Heading 2</option>
                        <option selected>Normal</option>
                    </select>
                </span>
                <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-strike"></button>
                </span>
                <span class="ql-formats">
                    <select class="ql-color"></select>
                    <select class="ql-background"></select>
                </span>
                <span class="ql-formats">
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>
                    <button class="ql-blockquote"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-clean"></button>
                </span>
            </div>
            <div id="editor-container">{!! $document->content !!}</div>
        </div>

        <!-- Right: Collaboration Sidebar -->
        <aside class="w-full lg:w-80 flex flex-col space-y-6">
            
            <!-- Online Users Card -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800 text-sm flex items-center">
                        <svg class="w-4 h-4 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Pengguna Aktif
                    </h3>
                    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2.5 py-0.5 rounded-full" id="online-count">1</span>
                </div>
                <ul id="online-users-list" class="space-y-2.5 max-h-48 overflow-y-auto">
                    <!-- Current User -->
                    <li class="flex items-center justify-between text-sm p-2 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-slate-700">{{ session('user_name', 'Anonymous') }} (Anda)</span>
                        </div>
                        <span class="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-mono" id="display-user-id">ID: {{ session('user_id') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Revision History Card -->
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-5 flex-1 flex flex-col">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800 text-sm flex items-center">
                        <svg class="w-4 h-4 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Riwayat Revisi
                    </h3>
                    <span class="text-xs text-slate-500" id="revisions-count">{{ $revisions->count() }} versi</span>
                </div>

                <div class="flex-1 overflow-y-auto max-h-96 pr-1 space-y-3" id="revisions-list">
                    @forelse($revisions as $rev)
                    <div class="p-3 bg-slate-50 hover:bg-indigo-50/50 border border-slate-100 hover:border-indigo-100 rounded-lg transition-all duration-150 cursor-pointer" onclick="viewRevision({{ $rev->id }})">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-slate-800">{{ $rev->author_name }}</span>
                            <span class="text-[10px] text-slate-500 bg-white px-1.5 py-0.5 rounded border border-slate-200 shadow-2xs">{{ $rev->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-slate-600 line-clamp-2 italic bg-white p-2 rounded border border-slate-100 mt-1">
                            {{ strip_tags($rev->content) ?: '(Dokumen kosong)' }}
                        </p>
                    </div>
                    @empty
                    <div class="text-center py-8 text-xs text-slate-400" id="no-revisions">
                        Belum ada riwayat revisi. Klik "Simpan Revisi" di atas untuk menyimpan versi pertama.
                    </div>
                    @endforelse
                </div>
            </div>

        </aside>

    </main>

    <!-- Name Modal -->
    <div id="name-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 w-full max-w-md mx-4 animate-scale-up">
            <h3 class="text-lg font-bold text-slate-800 mb-2">Pilih Nama Tampilan Anda</h3>
            <p class="text-xs text-slate-600 mb-6">Nama ini akan muncul di kursor Anda dan di daftar pengguna aktif saat berkolaborasi dengan orang lain.</p>
            
            <form onsubmit="saveName(event)" class="space-y-4">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap / Panggilan</label>
                    <input type="text" id="name-input" required class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 outline-none transition-all" placeholder="Contoh: Budi Santoso">
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <button type="button" onclick="closeNameModal()" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-xl transition-all cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-all cursor-pointer">Simpan Nama</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revision View Modal -->
    <div id="revision-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 w-full max-w-3xl mx-4 max-h-[90vh] flex flex-col animate-scale-up">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800" id="rev-modal-title">Detail Revisi</h3>
                    <p class="text-xs text-slate-500" id="rev-modal-subtitle"></p>
                </div>
                <button onclick="closeRevisionModal()" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 bg-slate-50 rounded-xl border border-slate-200 mb-4 ql-editor" id="rev-modal-content">
                <!-- Content injected via JS -->
            </div>

            <div class="flex justify-between items-center pt-3 border-t border-slate-200">
                <span class="text-xs text-slate-500">Catatan: Untuk mengembalikan versi ini, salin teks di atas ke editor utama.</span>
                <button type="button" onclick="closeRevisionModal()" class="px-5 py-2 text-sm font-medium text-white bg-slate-800 hover:bg-slate-900 rounded-xl shadow-sm transition-all cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openNameModal() {
            document.getElementById('name-modal').classList.remove('hidden');
        }
        function closeNameModal() {
            document.getElementById('name-modal').classList.add('hidden');
        }
        function closeRevisionModal() {
            document.getElementById('revision-modal').classList.add('hidden');
        }

        function saveName(event) {
            event.preventDefault();
            const newName = document.getElementById('name-input').value.trim();
            if (!newName) return;

            sessionStorage.setItem('user_name', newName);
            document.getElementById('display-user-name').innerText = newName;
            closeNameModal();

            const alertEl = document.getElementById('success-alert');
            if (alertEl) {
                alertEl.innerText = 'Nama berhasil diatur!';
                alertEl.style.display = 'block';
                setTimeout(() => alertEl.style.display = 'none', 4000);
            }

            window.location.reload();
        }

        window.addEventListener('DOMContentLoaded', () => {
            let tabId = sessionStorage.getItem('tab_id');
            if (!tabId) {
                tabId = Math.floor(Math.random() * 900000 + 100000).toString();
                sessionStorage.setItem('tab_id', tabId);
            }
            let userName = sessionStorage.getItem('user_name');
            if (!userName) {
                userName = 'User ' + Math.floor(Math.random() * 899 + 100);
                sessionStorage.setItem('user_name', userName);
            }

            document.getElementById('display-user-name').innerText = userName;
            const displayUserIdEl = document.getElementById('display-user-id');
            if (displayUserIdEl) displayUserIdEl.innerText = `ID: ${tabId}`;
            
            const nameInput = document.getElementById('name-input');
            if (nameInput) nameInput.value = userName.startsWith('User ') ? '' : userName;

            if (userName.startsWith('User ')) {
                openNameModal();
            }

            if (document.getElementById('success-alert')) {
                setTimeout(() => {
                    document.getElementById('success-alert').style.display = 'none';
                }, 4000);
            }
        });

        async function viewRevision(id) {
            try {
                const response = await fetch(`/revisions/${id}`);
                const data = await response.json();
                
                document.getElementById('rev-modal-title').innerText = `Revisi oleh ${data.author_name}`;
                document.getElementById('rev-modal-subtitle').innerText = `Disimpan pada: ${new Date(data.created_at).toLocaleString('id-ID')}`;
                document.getElementById('rev-modal-content').innerHTML = data.content || '<em class="text-slate-400">Dokumen kosong</em>';
                
                document.getElementById('revision-modal').classList.remove('hidden');
            } catch (error) {
                alert('Gagal memuat detail revisi.');
            }
        }
    </script>
</body>
</html>