(function ($) {
    'use strict';

    var STATE = {
        page: 1,
        pageSize: 10,
        keyword: '',
        status: 0,
        total: 0,
        list: [],
        selectedIds: []
    };

    var STATUS_MAP = {
        1: { text: '未使用', icon: 'fa-clock-o' },
        2: { text: '已使用', icon: 'fa-check-circle' },
        3: { text: '已过期', icon: 'fa-times-circle' }
    };

    var ACTION_MAP = {
        'create': '新增',
        'update': '编辑',
        'delete': '删除',
        'batch': '批量操作',
        'verify': '核销',
        'status_change': '状态变更',
        'export': '导出'
    };

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    function toast(message, type) {
        type = type || 'info';
        var iconMap = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        var titleMap = {
            success: '成功',
            error: '错误',
            warning: '警告',
            info: '提示'
        };

        var $toast = $([
            '<div class="toast toast-' + type + '">',
            '  <i class="fa ' + iconMap[type] + ' toast-icon"></i>',
            '  <div class="toast-body">',
            '    <p class="toast-title">' + titleMap[type] + '</p>',
            '    <p class="toast-msg">' + escapeHtml(message) + '</p>',
            '  </div>',
            '</div>'
        ].join(''));

        $('#toastContainer').append($toast);

        setTimeout(function () {
            $toast.addClass('toast-out');
            setTimeout(function () {
                $toast.remove();
            }, 300);
        }, 3000);
    }

    function formatDatetimeLocal(str) {
        if (!str) return '';
        var dt = str.replace(' ', 'T');
        if (dt.length > 16) {
            dt = dt.substring(0, 16);
        }
        return dt;
    }

    function formatDatetime(str) {
        if (!str) return '-';
        return escapeHtml(str);
    }

    function getDefaultExpireAt() {
        var d = new Date();
        d.setMonth(d.getMonth() + 1);
        d.setHours(23, 59, 0, 0);
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
               'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function loadStats() {
        $.getJSON('api/stats.php', function (res) {
            if (res.code === 0) {
                var d = res.data;
                $('#statTotal').text(d.total);
                $('#statTodayNew').text(d.today_new);
                $('#statTodayUsed').text(d.today_used);
                $('#statExpiring').text(d.expiring_soon);
                $('#statConversion').text(d.conversion_rate + '%');

                if (d.expiring_soon > 0) {
                    $('#statExpiring').closest('.stat-card').addClass('has-warning');
                } else {
                    $('#statExpiring').closest('.stat-card').removeClass('has-warning');
                }
            }
        });
    }

    function loadExpiringAlert() {
        $.getJSON('api/expiring.php', { days: 7 }, function (res) {
            if (res.code === 0) {
                var list = res.data.list;
                var total = res.data.total;
                if (total > 0) {
                    $('#expiringAlert').show();
                    $('#expiringCount').text(total);
                    renderExpiringList(list);
                } else {
                    $('#expiringAlert').hide();
                }
            }
        });
    }

    function renderExpiringList(list) {
        var html = '';
        var max = Math.min(list.length, 10);
        for (var i = 0; i < max; i++) {
            var item = list[i];
            var urgencyClass = item.remaining_hours < 24 ? 'expiring-urgent' : 'expiring-warning';
            html += '<div class="expiring-item ' + urgencyClass + '">';
            html += '  <span class="expiring-code"><i class="fa fa-ticket"></i> ' + escapeHtml(item.code) + '</span>';
            html += '  <span class="expiring-time"><i class="fa fa-clock-o"></i> ' + escapeHtml(item.expire_at) + '</span>';
            html += '  <span class="expiring-remaining">' + escapeHtml(item.remaining_text) + '过期</span>';
            if (item.remark) {
                html += '  <span class="expiring-remark">' + escapeHtml(item.remark) + '</span>';
            }
            html += '</div>';
        }
        if (list.length > 10) {
            html += '<div class="expiring-more">还有 ' + (list.length - 10) + ' 个即将过期...</div>';
        }
        $('#expiringList').html(html);
    }

    function loadList() {
        var params = {
            page: STATE.page,
            page_size: STATE.pageSize,
            keyword: STATE.keyword,
            status: STATE.status
        };

        $.getJSON('api/list.php', params, function (res) {
            if (res.code === 0) {
                STATE.total = res.data.total;
                STATE.page = res.data.page;
                STATE.pageSize = res.data.page_size;
                STATE.list = res.data.list;
                STATE.selectedIds = [];
                renderTable();
                renderPagination();
            } else {
                toast(res.message || '加载失败', 'error');
            }
        }).fail(function () {
            toast('网络错误，请稍后重试', 'error');
        });
    }

    function renderTable() {
        var $tbody = $('#tableBody');
        var $empty = $('#emptyState');
        var $table = $('#dataTable');
        $tbody.empty();

        if (!STATE.list || STATE.list.length === 0) {
            $table.hide();
            $empty.show();
            return;
        }

        $table.show();
        $empty.hide();

        STATE.list.forEach(function (item) {
            var statusInfo = STATUS_MAP[item.status] || STATUS_MAP[1];
            var usedBy = item.used_by ? escapeHtml(item.used_by) : '<span class="text-muted-cell">-</span>';
            var usedAt = item.used_at ? escapeHtml(item.used_at) : '<span class="text-muted-cell">-</span>';
            var usedIp = item.used_ip ? '<code>' + escapeHtml(item.used_ip) + '</code>' : '<span class="text-muted-cell">-</span>';
            var remark = item.remark ? escapeHtml(item.remark) : '<span class="text-muted-cell">-</span>';

            var row = [
                '<tr data-id="' + item.id + '">',
                '  <td class="checkbox-column">',
                '    <input type="checkbox" class="row-check" value="' + item.id + '">',
                '  </td>',
                '  <td><span class="code-text">' + escapeHtml(item.code) + '</span></td>',
                '  <td><span class="status-badge status-' + item.status + '"><i class="fa ' + statusInfo.icon + '"></i> ' + statusInfo.text + '</span></td>',
                '  <td>' + formatDatetime(item.expire_at) + '</td>',
                '  <td>' + usedBy + '</td>',
                '  <td>' + usedAt + '</td>',
                '  <td>' + usedIp + '</td>',
                '  <td>' + remark + '</td>',
                '  <td>' + formatDatetime(item.created_at) + '</td>',
                '  <td>',
                '    <div class="action-buttons">',
                '      <button class="action-btn view-btn" title="查看详情" data-id="' + item.id + '"><i class="fa fa-eye"></i></button>',
                '      <button class="action-btn copy-btn" title="复制" data-code="' + escapeHtml(item.code) + '"><i class="fa fa-copy"></i></button>',
                '      <button class="action-btn edit-btn" title="编辑" data-id="' + item.id + '"><i class="fa fa-pencil"></i></button>',
                '      <button class="action-btn delete-btn" title="删除" data-id="' + item.id + '"><i class="fa fa-trash-o"></i></button>',
                '    </div>',
                '  </td>',
                '</tr>'
            ].join('');
            $tbody.append(row);
        });

        $('#checkAll').prop('checked', false);
    }

    function renderPagination() {
        var $pagination = $('#pagination');
        var $info = $('#paginationInfo');
        var $jump = $('#jumpPage');

        $pagination.empty();

        if (STATE.total === 0) {
            $info.text('共 0 条记录');
            $jump.val(1);
            return;
        }

        var totalPages = Math.ceil(STATE.total / STATE.pageSize);
        var currentPage = STATE.page;
        $jump.val(currentPage);
        $jump.attr('max', totalPages);

        var from = (currentPage - 1) * STATE.pageSize + 1;
        var to = Math.min(currentPage * STATE.pageSize, STATE.total);
        $info.text('显示 ' + from + '-' + to + ' 条，共 ' + STATE.total + ' 条');

        $pagination.append('<li class="' + (currentPage === 1 ? 'disabled' : '') + '"><a href="javascript:;" data-page="' + Math.max(1, currentPage - 1) + '"><i class="fa fa-chevron-left"></i></a></li>');

        var start = Math.max(1, currentPage - 2);
        var end = Math.min(totalPages, currentPage + 2);

        if (start > 1) {
            $pagination.append('<li><a href="javascript:;" data-page="1">1</a></li>');
            if (start > 2) {
                $pagination.append('<li class="disabled"><span>...</span></li>');
            }
        }

        for (var i = start; i <= end; i++) {
            $pagination.append('<li class="' + (i === currentPage ? 'active' : '') + '"><a href="javascript:;" data-page="' + i + '">' + i + '</a></li>');
        }

        if (end < totalPages) {
            if (end < totalPages - 1) {
                $pagination.append('<li class="disabled"><span>...</span></li>');
            }
            $pagination.append('<li><a href="javascript:;" data-page="' + totalPages + '">' + totalPages + '</a></li>');
        }

        $pagination.append('<li class="' + (currentPage === totalPages ? 'disabled' : '') + '"><a href="javascript:;" data-page="' + Math.min(totalPages, currentPage + 1) + '"><i class="fa fa-chevron-right"></i></a></li>');
    }

    function copyToClipboard(text, $btn) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                handleCopySuccess($btn);
            }).catch(function () {
                fallbackCopy(text, $btn);
            });
        } else {
            fallbackCopy(text, $btn);
        }
    }

    function fallbackCopy(text, $btn) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.top = '-1000px';
        textarea.style.left = '-1000px';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            handleCopySuccess($btn);
        } catch (e) {
            toast('复制失败，请手动复制', 'error');
        } finally {
            document.body.removeChild(textarea);
        }
    }

    function handleCopySuccess($btn) {
        var $oldIcon = $btn.find('i');
        $btn.addClass('copied');
        $oldIcon.removeClass('fa-copy').addClass('fa-check');
        toast('复制成功', 'success');
        setTimeout(function () {
            $btn.removeClass('copied');
            $oldIcon.removeClass('fa-check').addClass('fa-copy');
        }, 1000);
    }

    function confirmAction(title, message, onOk) {
        $('#confirmTitle').html(title);
        $('#confirmMessage').text(message);
        $('#confirmModal').modal('show');

        $('#confirmOk').off('click').on('click', function () {
            $('#confirmModal').modal('hide');
            onOk();
        });
    }

    function openCreateModal() {
        $('#createForm')[0].reset();
        $('#autoGenerate').prop('checked', true);
        $('#codeGroup').hide();
        $('#createExpireAt').val(getDefaultExpireAt());
        $('#createModal').modal('show');
    }

    function submitCreate() {
        var auto = $('#autoGenerate').prop('checked');
        var code = auto ? "" : $.trim($('#createCode').val());
        var expireAt = $('#createExpireAt').val();
        var remark = $.trim($('#createRemark').val());

        if (!auto && !code) {
            toast('请输入邀请码', 'warning');
            return;
        }
        if (!expireAt) {
            toast('请选择有效期', 'warning');
            return;
        }

        var expireAtStr = expireAt.replace('T', ' ') + ':00';

        $.ajax({
            url: 'api/create.php',
            type: 'POST',
            dataType: 'json',
            data: {
                code: code,
                expire_at: expireAtStr,
                remark: remark
            },
            success: function (res) {
                if (res.code === 0) {
                    toast('添加成功，邀请码：' + res.data.code, 'success');
                    $('#createModal').modal('hide');
                    loadList();
                    loadStats();
                } else {
                    toast(res.message || '添加失败', 'error');
                }
            },
            error: function () {
                toast('网络错误，请稍后重试', 'error');
            }
        });
    }

    function openEditModal(id) {
        var item = STATE.list.find(function (i) { return i.id == id; });
        if (!item) return;

        $('#editId').val(item.id);
        $('#editCode').val(item.code);
        $('#editStatus').val(item.status);
        $('#editExpireAt').val(formatDatetimeLocal(item.expire_at));
        $('#editUsedBy').val(item.used_by || '');
        $('#editRemark').val(item.remark || '');

        toggleUsedByRequired(item.status);
        $('#editModal').modal('show');
    }

    function toggleUsedByRequired(status) {
        var $label = $('#editUsedByLabel');
        var $input = $('#editUsedBy');
        if (status == 2) {
            $label.html('使用人 <span class="required">*</span>');
            $input.attr('placeholder', '请输入使用人（必填）');
        } else {
            $label.text('使用人');
            $input.attr('placeholder', '状态为已使用时必填');
        }
    }

    function submitEdit() {
        var id = $('#editId').val();
        var status = $('#editStatus').val();
        var expireAt = $('#editExpireAt').val();
        var usedBy = $.trim($('#editUsedBy').val());
        var remark = $.trim($('#editRemark').val());

        if (!expireAt) {
            toast('请选择有效期', 'warning');
            return;
        }
        if (status == 2 && !usedBy) {
            toast('状态为已使用时，使用人不能为空', 'warning');
            return;
        }

        var expireAtStr = expireAt.replace('T', ' ') + ':00';

        $.ajax({
            url: 'api/update.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                status: status,
                expire_at: expireAtStr,
                used_by: usedBy,
                remark: remark
            },
            success: function (res) {
                if (res.code === 0) {
                    toast('更新成功', 'success');
                    $('#editModal').modal('hide');
                    loadList();
                    loadStats();
                } else {
                    toast(res.message || '更新失败', 'error');
                }
            },
            error: function () {
                toast('网络错误，请稍后重试', 'error');
            }
        });
    }

    function deleteOne(id) {
        confirmAction(
            '<i class="fa fa-question-circle" style="color:#ef4444;"></i> 确认删除',
            '确定要删除该邀请码吗？删除后不可恢复。',
            function () {
                $.ajax({
                    url: 'api/delete.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id },
                    success: function (res) {
                        if (res.code === 0) {
                            toast('删除成功', 'success');
                            loadList();
                            loadStats();
                        } else {
                            toast(res.message || '删除失败', 'error');
                        }
                    },
                    error: function () {
                        toast('网络错误，请稍后重试', 'error');
                    }
                });
            }
        );
    }

    function getSelectedIds() {
        var ids = [];
        $('.row-check:checked').each(function () {
            ids.push(parseInt($(this).val(), 10));
        });
        return ids;
    }

    function batchDelete() {
        var ids = getSelectedIds();
        if (ids.length === 0) {
            toast('请先选择要删除的邀请码', 'warning');
            return;
        }

        confirmAction(
            '<i class="fa fa-question-circle" style="color:#ef4444;"></i> 确认批量删除',
            '确定要删除选中的 ' + ids.length + ' 条邀请码吗？删除后不可恢复。',
            function () {
                $.ajax({
                    url: 'api/batch_delete.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { ids: ids },
                    success: function (res) {
                        if (res.code === 0) {
                            toast(res.message || '删除成功', 'success');
                            loadList();
                            loadStats();
                        } else {
                            toast(res.message || '删除失败', 'error');
                        }
                    },
                    error: function () {
                        toast('网络错误，请稍后重试', 'error');
                    }
                });
            }
        );
    }

    function openBatchCreateModal() {
        $('#batchCreateForm')[0].reset();
        $('#batchCount').val(10);
        $('#batchExpireAt').val(getDefaultExpireAt());
        $('#batchCreateModal').modal('show');
    }

    function submitBatchCreate() {
        var count = parseInt($('#batchCount').val(), 10);
        var expireAt = $('#batchExpireAt').val();
        var remark = $.trim($('#batchRemark').val());

        if (!count || count < 1 || count > 1000) {
            toast('请输入有效的生成数量（1-1000）', 'warning');
            return;
        }
        if (!expireAt) {
            toast('请选择有效期', 'warning');
            return;
        }

        var expireAtStr = expireAt.replace('T', ' ') + ':00';
        var $btn = $('#submitBatchCreate');
        var origText = $btn.text();
        $btn.prop('disabled', true).text('生成中...');

        $.ajax({
            url: 'api/batch_create.php',
            type: 'POST',
            dataType: 'json',
            data: {
                count: count,
                expire_at: expireAtStr,
                remark: remark
            },
            success: function (res) {
                if (res.code === 0) {
                    toast(res.message || '批量生成成功', 'success');
                    $('#batchCreateModal').modal('hide');
                    STATE.page = 1;
                    loadList();
                    loadStats();
                } else {
                    toast(res.message || '生成失败', 'error');
                }
            },
            error: function () {
                toast('网络错误，请稍后重试', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(origText);
            }
        });
    }

    function doExport() {
        var params = [];
        params.push('keyword=' + encodeURIComponent(STATE.keyword));
        params.push('status=' + STATE.status);
        var url = 'api/export.php?' + params.join('&');
        window.open(url, '_blank');
        toast('正在导出CSV文件...', 'info');
    }

    function openDetailModal(id) {
        $.getJSON('api/detail.php', { id: id }, function (res) {
            if (res.code === 0) {
                var d = res.data.detail;
                var logs = res.data.logs;

                $('#detailCode').text(d.code);
                $('#detailStatus').html('<span class="status-badge status-' + d.status + '"><i class="fa ' + STATUS_MAP[d.status].icon + '"></i> ' + d.status_text + '</span>');
                $('#detailExpireAt').text(d.expire_at || '-');
                $('#detailRemaining').text(d.remaining_text || '-');
                $('#detailUsedBy').text(d.used_by || '-');
                $('#detailUsedAt').text(d.used_at || '-');
                $('#detailUsedIp').text(d.used_ip || '-');
                $('#detailCreatedAt').text(d.created_at || '-');
                $('#detailRemark').text(d.remark || '-');

                renderDetailLogs(logs);
                $('#detailModal').modal('show');
            } else {
                toast(res.message || '获取详情失败', 'error');
            }
        }).fail(function () {
            toast('网络错误，请稍后重试', 'error');
        });
    }

    function renderDetailLogs(logs) {
        var $list = $('#detailLogList');
        $list.empty();

        if (!logs || logs.length === 0) {
            $list.html('<div class="detail-log-empty">暂无操作日志</div>');
            return;
        }

        logs.forEach(function (log) {
            var actionText = ACTION_MAP[log.action] || log.action;
            var icon = '';
            var colorClass = '';

            switch (log.action) {
                case 'create':
                    icon = 'fa-plus-circle';
                    colorClass = 'log-create';
                    break;
                case 'update':
                case 'status_change':
                    icon = 'fa-pencil';
                    colorClass = 'log-update';
                    break;
                case 'delete':
                    icon = 'fa-trash-o';
                    colorClass = 'log-delete';
                    break;
                case 'verify':
                    icon = 'fa-check-circle';
                    colorClass = 'log-verify';
                    break;
                case 'export':
                    icon = 'fa-download';
                    colorClass = 'log-export';
                    break;
                default:
                    icon = 'fa-info-circle';
                    colorClass = 'log-default';
            }

            var contentPreview = '';
            if (log.content_arr && typeof log.content_arr === 'object') {
                if (log.content_arr.code) {
                    contentPreview = '邀请码: ' + log.content_arr.code;
                } else if (log.content_arr.id) {
                    contentPreview = 'ID: ' + log.content_arr.id;
                }
            }

            var html = [
                '<div class="detail-log-item ' + colorClass + '">',
                '  <div class="detail-log-icon"><i class="fa ' + icon + '"></i></div>',
                '  <div class="detail-log-body">',
                '    <div class="detail-log-title">',
                '      <span class="detail-log-action">' + escapeHtml(actionText) + '</span>',
                '      <span class="detail-log-title-text">' + escapeHtml(log.title) + '</span>',
                '    </div>',
                '    <div class="detail-log-meta">',
                '      <span><i class="fa fa-clock-o"></i> ' + escapeHtml(log.created_at) + '</span>',
                '      <span><i class="fa fa-user"></i> ' + escapeHtml(log.admin_name || '系统') + '</span>',
                '      <span><i class="fa fa-globe"></i> ' + escapeHtml(log.ip || '-') + '</span>',
                '    </div>',
                '    ' + (contentPreview ? '<div class="detail-log-content">' + escapeHtml(contentPreview) + '</div>' : ''),
                '  </div>',
                '</div>'
            ].join('');

            $list.append(html);
        });
    }

    function bindEvents() {
        $('#createBtn').on('click', openCreateModal);
        $('#batchCreateBtn').on('click', openBatchCreateModal);
        $('#batchDeleteBtn').on('click', batchDelete);
        $('#submitCreate').on('click', submitCreate);
        $('#submitEdit').on('click', submitEdit);
        $('#submitBatchCreate').on('click', submitBatchCreate);
        $('#exportBtn').on('click', doExport);
        $('#refreshBtn').on('click', function () { loadList(); loadStats(); loadExpiringAlert(); toast('已刷新', 'info'); });

        $('#searchBtn').on('click', function () {
            STATE.keyword = $.trim($('#searchInput').val());
            STATE.status = parseInt($('#statusFilter').val(), 10) || 0;
            STATE.page = 1;
            loadList();
        });

        $('#searchInput').on('keypress', function (e) {
            if (e.which === 13) {
                $('#searchBtn').trigger('click');
            }
        });

        $('#statusFilter').on('change', function () {
            $('#searchBtn').trigger('click');
        });

        $('#autoGenerate').on('change', function () {
            if ($(this).prop('checked')) {
                $('#codeGroup').hide();
                $('#createCode').val('');
            } else {
                $('#codeGroup').show();
            }
        });

        $('#editStatus').on('change', function () {
            toggleUsedByRequired($(this).val());
        });

        $('#pagination').on('click', 'a[data-page]', function (e) {
            e.preventDefault();
            var page = parseInt($(this).attr('data-page'), 10);
            if (page && page !== STATE.page) {
                STATE.page = page;
                loadList();
            }
        });

        $('#jumpBtn').on('click', function () {
            var page = parseInt($('#jumpPage').val(), 10);
            var totalPages = Math.ceil(STATE.total / STATE.pageSize);
            if (!page || page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            if (page !== STATE.page) {
                STATE.page = page;
                loadList();
            }
        });

        $('#jumpPage').on('keypress', function (e) {
            if (e.which === 13) {
                $('#jumpBtn').trigger('click');
            }
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', $(this).prop('checked'));
        });

        $('#tableBody').on('change', '.row-check', function () {
            var total = $('.row-check').length;
            var checked = $('.row-check:checked').length;
            $('#checkAll').prop('checked', total > 0 && total === checked);
        });

        $('#tableBody').on('click', '.copy-btn', function () {
            var code = $(this).attr('data-code');
            copyToClipboard(code, $(this));
        });

        $('#tableBody').on('click', '.view-btn', function () {
            var id = $(this).attr('data-id');
            openDetailModal(id);
        });

        $('#tableBody').on('click', '.edit-btn', function () {
            var id = $(this).attr('data-id');
            openEditModal(id);
        });

        $('#tableBody').on('click', '.delete-btn', function () {
            var id = $(this).attr('data-id');
            deleteOne(id);
        });

        $('#toggleExpiring').on('click', function () {
            var $body = $('#expiringBody');
            var $icon = $(this).find('i');
            if ($body.is(':visible')) {
                $body.slideUp(200);
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                $body.slideDown(200);
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
    }

    $(function () {
        bindEvents();
        loadStats();
        loadExpiringAlert();
        loadList();
    });

})(jQuery);
