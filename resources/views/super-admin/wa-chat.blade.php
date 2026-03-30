@extends('layouts.admin')

@section('title', 'WA Chat')

@section('content')
    <div class="container-fluid">
        <div class="row" style="min-height: calc(100vh - 190px);">
            <div class="col-lg-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h3 class="card-title">Inbox Percakapan</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="form-group mb-2">
                            <select id="wa-chat-status" class="form-control form-control-sm">
                                <option value="">Semua Status</option>
                                <option value="open">Open</option>
                                <option value="pending">Pending</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <input id="wa-chat-search" type="text" class="form-control form-control-sm" placeholder="Cari nama atau nomor">
                        </div>
                        <div id="wa-chat-conversations" class="list-group list-group-flush">
                            <div class="text-muted small text-center py-4">Memuat percakapan...</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 d-flex">
                <div class="card flex-fill d-flex flex-column">
                    <div class="card-header d-none" id="wa-chat-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
                            <div>
                                <strong id="wa-chat-contact-name">-</strong>
                                <small class="text-muted ml-2" id="wa-chat-contact-phone"></small>
                                <span class="badge badge-secondary ml-2" id="wa-chat-status-badge">open</span>
                                <span class="ml-2" id="wa-chat-customer"></span>
                            </div>
                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                <select id="wa-chat-assign-user" class="form-control form-control-sm" style="width: 180px;">
                                    <option value="">Assign ke user</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="saveWaChatAssignment()">Simpan Assign</button>
                                <button type="button" class="btn btn-success btn-sm" onclick="markWaChatResolved()">Resolved</button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="markWaChatOpen()">Buka Lagi</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteWaConversation()">Hapus</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="wa-chat-messages" style="overflow-y: auto; background: #f4f6f9; min-height: 420px;">
                        <div class="text-center text-muted py-5" id="wa-chat-empty">
                            Pilih percakapan untuk membaca pesan.
                        </div>
                    </div>
                    <div class="card-footer d-none" id="wa-chat-input">
                        <div class="input-group">
                            <textarea id="wa-chat-reply" class="form-control" rows="2" placeholder="Tulis balasan..."></textarea>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success" onclick="sendWaReply()">Kirim</button>
                            </div>
                        </div>
                        <div id="wa-chat-action-alert" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const waChatRoutes = {
            conversations: @json(route('super-admin.wa-chat.conversations')),
            assignableUsers: @json(route('super-admin.wa-chat.assignable-users')),
            showBase: @json(url('/super-admin/wa-chat/conversations')),
        };

        let activeConversationId = null;
        let waChatUsersLoaded = false;

        function waChatEscape(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function waChatAlert(message, type) {
            const target = document.getElementById('wa-chat-action-alert');

            if (! target) {
                return;
            }

            target.innerHTML = '<div class="alert alert-' + type + ' py-2 px-3 mb-0">' + message + '</div>';
        }

        function waChatStatusBadgeClass(status) {
            if (status === 'resolved') {
                return 'badge-secondary';
            }

            if (status === 'pending') {
                return 'badge-warning';
            }

            return 'badge-success';
        }

        function waChatBubble(message) {
            const alignment = message.direction === 'outbound' ? 'justify-content-end' : 'justify-content-start';
            const bubbleClass = message.direction === 'outbound' ? 'bg-success text-white' : 'bg-white border';
            const sender = message.sender_name ? '<div class="small font-weight-bold mb-1">' + waChatEscape(message.sender_name) + '</div>' : '';

            return '<div class="d-flex ' + alignment + ' mb-3">' +
                '<div class="rounded px-3 py-2 ' + bubbleClass + '" style="max-width: 75%;">' +
                sender +
                '<div style="white-space: pre-wrap;">' + waChatEscape(message.message || '') + '</div>' +
                '<div class="small mt-1 ' + (message.direction === 'outbound' ? 'text-white-50' : 'text-muted') + '">' + waChatEscape(message.created_at_human || '') + '</div>' +
                '</div>' +
                '</div>';
        }

        function loadWaConversations() {
            const query = new URLSearchParams({
                status: document.getElementById('wa-chat-status').value,
                search: document.getElementById('wa-chat-search').value,
            });

            fetch(waChatRoutes.conversations + '?' + query.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    const target = document.getElementById('wa-chat-conversations');
                    const conversations = data.data || [];

                    if (conversations.length === 0) {
                        target.innerHTML = '<div class="text-muted small text-center py-4">Belum ada percakapan.</div>';

                        return;
                    }

                    target.innerHTML = '';

                    conversations.forEach(function (conversation) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'list-group-item list-group-item-action text-left' + (Number(conversation.id) === Number(activeConversationId) ? ' active' : '');
                        button.innerHTML =
                            '<div class="d-flex justify-content-between"><strong>' + waChatEscape(conversation.contact_name || conversation.contact_phone) + '</strong><small>' + waChatEscape(conversation.last_message_at || '') + '</small></div>' +
                            '<div class="small text-muted">' + waChatEscape(conversation.contact_phone || '') + '</div>' +
                            '<div class="d-flex justify-content-between align-items-center mt-1"><small>' + waChatEscape(conversation.last_message || '-') + '</small><span class="badge ' + waChatStatusBadgeClass(conversation.status) + '">' + waChatEscape(conversation.status) + '</span></div>';
                        button.onclick = function () {
                            openWaConversation(conversation.id);
                        };
                        target.appendChild(button);
                    });
                });
        }

        function ensureWaChatUsersLoaded(selectedUserId) {
            if (waChatUsersLoaded) {
                if (selectedUserId) {
                    document.getElementById('wa-chat-assign-user').value = String(selectedUserId);
                }

                return;
            }

            fetch(waChatRoutes.assignableUsers, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (users) {
                    const select = document.getElementById('wa-chat-assign-user');
                    select.innerHTML = '<option value="">Assign ke user</option>';

                    users.forEach(function (user) {
                        const option = document.createElement('option');
                        option.value = String(user.id);
                        option.textContent = user.label;
                        select.appendChild(option);
                    });

                    if (selectedUserId) {
                        select.value = String(selectedUserId);
                    }

                    waChatUsersLoaded = true;
                });
        }

        function openWaConversation(conversationId) {
            activeConversationId = Number(conversationId);

            fetch(waChatRoutes.showBase + '/' + conversationId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    const conversation = data.conversation;
                    const messages = data.messages || [];
                    const header = document.getElementById('wa-chat-header');
                    const input = document.getElementById('wa-chat-input');
                    const empty = document.getElementById('wa-chat-empty');
                    const messagesTarget = document.getElementById('wa-chat-messages');
                    const customerTarget = document.getElementById('wa-chat-customer');
                    const statusBadge = document.getElementById('wa-chat-status-badge');

                    document.getElementById('wa-chat-contact-name').textContent = conversation.contact_name || conversation.contact_phone;
                    document.getElementById('wa-chat-contact-phone').textContent = conversation.contact_phone || '';
                    statusBadge.textContent = conversation.status;
                    statusBadge.className = 'badge ml-2 ' + waChatStatusBadgeClass(conversation.status);
                    customerTarget.innerHTML = conversation.customer
                        ? '<a class="badge badge-info" href="' + waChatEscape(conversation.customer.url) + '">' + waChatEscape(conversation.customer.name) + '</a>'
                        : '<span class="badge badge-secondary">Non-pelanggan</span>';

                    header.classList.remove('d-none');
                    input.classList.remove('d-none');
                    empty.classList.add('d-none');
                    messagesTarget.innerHTML = messages.map(waChatBubble).join('');
                    messagesTarget.scrollTop = messagesTarget.scrollHeight;

                    ensureWaChatUsersLoaded(conversation.assigned_to_id || null);
                    loadWaConversations();
                });
        }

        function waChatJsonPost(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(payload || {}),
            }).then(function (response) {
                return response.json();
            });
        }

        function sendWaReply() {
            if (! activeConversationId) {
                return;
            }

            const message = document.getElementById('wa-chat-reply').value.trim();

            if (message === '') {
                waChatAlert('Pesan balasan tidak boleh kosong.', 'warning');

                return;
            }

            waChatJsonPost(waChatRoutes.showBase + '/' + activeConversationId + '/reply', {message: message})
                .then(function (data) {
                    if (! data.success) {
                        throw new Error(data.message || 'Gagal mengirim balasan.');
                    }

                    document.getElementById('wa-chat-reply').value = '';
                    waChatAlert(data.message, 'success');
                    openWaConversation(activeConversationId);
                })
                .catch(function (error) {
                    waChatAlert(error.message, 'danger');
                });
        }

        function saveWaChatAssignment() {
            if (! activeConversationId) {
                return;
            }

            waChatJsonPost(waChatRoutes.showBase + '/' + activeConversationId + '/assign', {
                assigned_to_id: document.getElementById('wa-chat-assign-user').value || null,
            }).then(function () {
                waChatAlert('Assign percakapan berhasil diperbarui.', 'success');
                loadWaConversations();
            });
        }

        function markWaChatResolved() {
            if (! activeConversationId) {
                return;
            }

            waChatJsonPost(waChatRoutes.showBase + '/' + activeConversationId + '/resolve')
                .then(function () {
                    waChatAlert('Percakapan ditandai resolved.', 'success');
                    openWaConversation(activeConversationId);
                });
        }

        function markWaChatOpen() {
            if (! activeConversationId) {
                return;
            }

            waChatJsonPost(waChatRoutes.showBase + '/' + activeConversationId + '/open')
                .then(function () {
                    waChatAlert('Percakapan dibuka kembali.', 'success');
                    openWaConversation(activeConversationId);
                });
        }

        function deleteWaConversation() {
            if (! activeConversationId) {
                return;
            }

            fetch(waChatRoutes.showBase + '/' + activeConversationId, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (! data.success) {
                        throw new Error(data.message || 'Gagal menghapus percakapan.');
                    }

                    activeConversationId = null;
                    document.getElementById('wa-chat-header').classList.add('d-none');
                    document.getElementById('wa-chat-input').classList.add('d-none');
                    document.getElementById('wa-chat-messages').innerHTML = '<div class="text-center text-muted py-5" id="wa-chat-empty">Pilih percakapan untuk membaca pesan.</div>';
                    loadWaConversations();
                })
                .catch(function (error) {
                    waChatAlert(error.message, 'danger');
                });
        }

        document.getElementById('wa-chat-status').addEventListener('change', loadWaConversations);

        let waChatSearchTimer = null;

        document.getElementById('wa-chat-search').addEventListener('input', function () {
            clearTimeout(waChatSearchTimer);
            waChatSearchTimer = setTimeout(loadWaConversations, 250);
        });

        loadWaConversations();
        setInterval(loadWaConversations, 5000);
    </script>
@endpush
