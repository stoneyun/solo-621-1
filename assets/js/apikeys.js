(function ($) {
    'use strict';

    var STATE = {
        page: 1,
        pageSize: 10,
        keyword: '',
        total: 0,
        list: []
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

    function formatDatetime(str) {
        if (!str) return '-';
        return escapeHtml(str);
    }

    function maskApiKey(key) {
        if (!key) return '';
        if (key.length <= 8) return key;
        return key.substring(0, 4) + '****' + key.substring(key.length - 4);
    }

    function loadList() {
        var params = {
            page: STATE.page,
            page_size: STATE.pageSize,
            keyword: STATE.keyword
        };

        $.getJSON('api/apikey/list.php', params, function (res) {
            if (res.code === 0) {
                STATE.total = res.data.total;
                STATE.page = res.data.page;
                STATE.pageSize = res.data.page_size;
                STATE.list = res.data.list;
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
            var row = [
                '<tr data-id="' + item.id + '">',
                '  <td>' + item.id + '</td>',
                '  <td>' + escapeHtml(item.name) + '</td>',
                '  <td>',
                '    <code class="api-key-text" data-full="' + escapeHtml(item.api_key) + '">' + maskApiKey(item.api_key) + '</code>',
                '    <button class="btn btn-default btn-xs copy-btn" data-key="' + escapeHtml(item.api_key) + '" style="margin-left:8px;padding:2px 8px;font-size:12px;">',
                '      <i class="fa fa-copy"></i> 复制',
                '    </button>',
                '  </td>',
                '  <td>' + formatDatetime(item.created_at) + '</td>',
                '  <td>',
                '    <div class="action-buttons">',
                '      <button class="action-btn delete-btn" title="删除" data-id="' + item.id + '" data-name="' + escapeHtml(item.name) + '"><i class="fa fa-trash-o"></i></button>',
                '    </div>',
                '  </td>',
                '</tr>'
            ].join('');
            $tbody.append(row);
        });
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
        }, 1500);
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
        $('#createModal').modal('show');
    }

    function submitCreate() {
        var name = $.trim($('#createName').val());
        var apiKey = $.trim($('#createApiKey').val());

        if (!name) {
            toast('请输入密钥名称', 'warning');
            return;
        }

        $.ajax({
            url: 'api/apikey/create.php',
            type: 'POST',
            dataType: 'json',
            data: {
                name: name,
                api_key: apiKey
            },
            success: function (res) {
                if (res.code === 0) {
                    toast('添加成功，密钥：' + res.data.api_key, 'success');
                    $('#createModal').modal('hide');
                    loadList();
                } else {
                    toast(res.message || '添加失败', 'error');
                }
            },
            error: function () {
                toast('网络错误，请稍后重试', 'error');
            }
        });
    }

    function deleteOne(id, name) {
        confirmAction(
            '<i class="fa fa-question-circle" style="color:#ef4444;"></i> 确认删除',
            '确定要删除API密钥「' + name + '」吗？删除后不可恢复，使用该密钥的业务系统将无法调用接口。',
            function () {
                $.ajax({
                    url: 'api/apikey/delete.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id },
                    success: function (res) {
                        if (res.code === 0) {
                            toast('删除成功', 'success');
                            loadList();
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

    function bindEvents() {
        $('#createBtn').on('click', openCreateModal);
        $('#submitCreate').on('click', submitCreate);

        $('#searchBtn').on('click', function () {
            STATE.keyword = $.trim($('#searchInput').val());
            STATE.page = 1;
            loadList();
        });

        $('#searchInput').on('keypress', function (e) {
            if (e.which === 13) {
                $('#searchBtn').trigger('click');
            }
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

        $('#tableBody').on('click', '.copy-btn', function () {
            var key = $(this).attr('data-key');
            copyToClipboard(key, $(this));
        });

        $('#tableBody').on('click', '.delete-btn', function () {
            var id = $(this).attr('data-id');
            var name = $(this).attr('data-name');
            deleteOne(id, name);
        });
    }

    $(function () {
        bindEvents();
        loadList();
    });

})(jQuery);
