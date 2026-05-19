import './bootstrap';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import 'quill-cursors/css';

Quill.register('modules/cursors', QuillCursors);

document.addEventListener('DOMContentLoaded', () => {
    const editorContainer = document.getElementById('editor-container');
    if (!editorContainer) return;

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

    const documentId = document.querySelector('meta[name="document-id"]').content;
    const currentUserName = userName;
    const currentUserId = tabId;

    // Initialize Quill Editor
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            cursors: {
                transformOnTextChange: true,
            },
            toolbar: '#editor-toolbar'
        },
        placeholder: 'Mulai mengetik dokumen kolaboratif Anda di sini...'
    });

    const cursors = quill.getModule('cursors');
    const userColors = {};
    const availableColors = ['#ef4444', '#f97316', '#eab308', '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899'];

    function getUserColor(userId) {
        if (!userColors[userId]) {
            const index = Math.abs(userId.hashCode ? userId.hashCode() : parseInt(userId)) % availableColors.length;
            userColors[userId] = availableColors[index] || '#3b82f6';
        }
        return userColors[userId];
    }

    // Presence Channel Subscription
    const channel = window.Echo.join(`document.${documentId}`);

    channel.here((users) => {
        updateOnlineUsersList(users);
    })
        .joining((user) => {
            addUserToList(user);
            // Create cursor if not exists
            cursors.createCursor(user.id.toString(), user.name, getUserColor(user.id));
            cursors.toggleFlag(user.id.toString(), true);
        })
        .leaving((user) => {
            removeUserFromList(user.id);
            cursors.removeCursor(user.id.toString());
        });

    // Listen for local text changes and broadcast via Whisper
    quill.on('text-change', (delta, oldDelta, source) => {
        if (source === 'user') {
            channel.whisper('text-change', {
                delta: delta,
                userId: currentUserId
            });
        }
    });

    // Listen for local cursor movements and broadcast via Whisper
    quill.on('selection-change', (range, oldRange, source) => {
        if (source === 'user' && range) {
            channel.whisper('cursor-move', {
                range: range,
                userId: currentUserId,
                userName: currentUserName
            });
        }
    });

    // Listen for remote text changes
    channel.listenForWhisper('text-change', (e) => {
        quill.updateContents(e.delta, 'silent');
        cursors.update();
    });

    // Listen for remote cursor movements
    channel.listenForWhisper('cursor-move', (e) => {
        const cursorId = e.userId.toString();
        const existing = cursors.cursors().find(c => c.id === cursorId);
        if (!existing) {
            cursors.createCursor(cursorId, e.userName, getUserColor(e.userId));
        }
        cursors.moveCursor(cursorId, e.range);
        cursors.toggleFlag(cursorId, true);
    });

    // Save Revision Logic
    const saveBtn = document.getElementById('save-revision-btn');
    const savingAlert = document.getElementById('saving-alert');

    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            saveBtn.disabled = true;
            savingAlert.classList.remove('hidden');

            try {
                const response = await window.axios.post(`/documents/${documentId}/revisions`, {
                    content: quill.root.innerHTML,
                    author_name: currentUserName
                });

                if (response.data.success) {
                    // Refresh revisions list
                    fetchRevisions();
                }
            } catch (error) {
                alert('Terjadi kesalahan saat menyimpan revisi.');
            } finally {
                saveBtn.disabled = false;
                savingAlert.classList.add('hidden');
            }
        });
    }

    async function fetchRevisions() {
        try {
            const response = await window.axios.get(`/documents/${documentId}/revisions`);
            const revisions = response.data;
            const listContainer = document.getElementById('revisions-list');
            document.getElementById('revisions-count').innerText = `${revisions.length} versi`;

            if (revisions.length === 0) {
                listContainer.innerHTML = `<div class="text-center py-8 text-xs text-slate-400">Belum ada riwayat revisi.</div>`;
                return;
            }

            let html = '';
            revisions.forEach(rev => {
                const dateStr = new Date(rev.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const plainText = rev.content ? rev.content.replace(/<[^>]*>?/gm, '').substring(0, 100) : '(Dokumen kosong)';
                html += `
                <div class="p-3 bg-slate-50 hover:bg-indigo-50/50 border border-slate-100 hover:border-indigo-100 rounded-lg transition-all duration-150 cursor-pointer" onclick="viewRevision(${rev.id})">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-slate-800">${rev.author_name}</span>
                        <span class="text-[10px] text-slate-500 bg-white px-1.5 py-0.5 rounded border border-slate-200 shadow-2xs">${dateStr}</span>
                    </div>
                    <p class="text-xs text-slate-600 line-clamp-2 italic bg-white p-2 rounded border border-slate-100 mt-1">
                        ${plainText || '(Dokumen kosong)'}
                    </p>
                </div>`;
            });

            listContainer.innerHTML = html;
        } catch (error) {
            console.error('Failed to fetch revisions', error);
        }
    }

    function updateOnlineUsersList(users) {
        document.getElementById('online-count').innerText = users.length;
        const list = document.getElementById('online-users-list');
        list.innerHTML = '';

        users.forEach(user => {
            const isMe = user.id.toString() === currentUserId.toString();
            const color = isMe ? '#10b981' : getUserColor(user.id);
            const li = document.createElement('li');
            li.id = `user-li-${user.id}`;
            li.className = "flex items-center justify-between text-sm p-2 rounded-lg bg-slate-50 border border-slate-100";
            li.innerHTML = `
                <div class="flex items-center space-x-2.5">
                    <span class="w-2 h-2 rounded-full" style="background-color: ${color}"></span>
                    <span class="font-medium text-slate-700">${user.name} ${isMe ? '(Anda)' : ''}</span>
                </div>
                <span class="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-mono">ID: ${user.id}</span>
            `;
            list.appendChild(li);
        });
    }

    function addUserToList(user) {
        const countEl = document.getElementById('online-count');
        countEl.innerText = parseInt(countEl.innerText) + 1;

        const list = document.getElementById('online-users-list');
        if (document.getElementById(`user-li-${user.id}`)) return;

        const li = document.createElement('li');
        li.id = `user-li-${user.id}`;
        li.className = "flex items-center justify-between text-sm p-2 rounded-lg bg-slate-50 border border-slate-100 animate-scale-up";
        li.innerHTML = `
            <div class="flex items-center space-x-2.5">
                <span class="w-2 h-2 rounded-full" style="background-color: ${getUserColor(user.id)}"></span>
                <span class="font-medium text-slate-700">${user.name}</span>
            </div>
            <span class="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-mono">ID: ${user.id}</span>
        `;
        list.appendChild(li);
    }

    function removeUserFromList(userId) {
        const countEl = document.getElementById('online-count');
        countEl.innerText = Math.max(0, parseInt(countEl.innerText) - 1);

        const el = document.getElementById(`user-li-${userId}`);
        if (el) el.remove();
    }
});