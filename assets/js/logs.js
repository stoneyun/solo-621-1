(function ($) {
    'use strict';

    var STATE = {
        page: 1,
        pageSize: 20,
        total: 0,
        keyword: '',
        module: '',
        action: '',
        list: []
    };

    var MODULE_MAP = {};
    var ACTION_MAP = {};

    function loadDict() {
        var modules = {
            'invitation': '邀请码',
            'admin': '管理员',
            'group': '分组权限',
            'log': '操作日志',
            'auth': '登录认证'
        };
        var actions = {
            'login': '登录',
            'logout': '登出',
            'view': '查看',
            'create': '新增',
            'update': '编辑',
            'delete': '删除',
            'batch_create': '批量生成',
            'batch_delete': '批量删除',
            'reset_password': '重置密码',
            'change_password': '修改密码',
            'login_fail': '登录失败'
        };

        MODULE_MAP = modules;
        ACTION_MAP = actions;

        var mHtml = '<option value="">全部模块</option>';
        $.each(modules, function (k, v) {
            mHtml += '<option value="' + k + '">' + v + '</option>';
        });
        $('#moduleFilter').html(mHtml);

        var aHtml = '<option value="">全部操作</option>';
        $.each(actions, function (k, v) {
            aHtml += '<option value="' + k + '">' + v + '</option>';
        });
        $('#actionFilter').html(aHtml);
    }

    function loadList() {
        var params = {
            page: STATE.page,
            page_size: STATE.pageSize,
            keyword: STATE.keyword,
            module: STATE.module,
            action: STATE.action
        };

        App.ajax('api/log/list.php', {
            data: params,
            success: function (res) {
                if (res.code === 0) {
                    STATE.total = res.data.total;
                    STATE.list = res.data.list;
                    renderTable();
                    renderPagination();
                }
            }
        });
    }

    function getModuleText(mod) {
        return MODULE_MAP[mod] || mod;
    }

    function getActionText(act) {
        return ACTION_MAP[act] || act;
    }

    function getActionBadgeClass(act) {
        var map = {
            'login': 'success',
            'logout': 'default',
            'create': 'primary',
            'update': 'warning',
            'delete': 'danger',
            'batch_create': 'primary',
            'batch_delete': 'danger',
            'reset_password': 'danger',
            'change_password': 'warning',
            'view': 'info',
            'verify': 'success',
            'export': 'info',
            'login_fail': 'danger'
        };
        return map[act] || 'default';
    }

    function renderTable() {
        var $tbody = $('#tableBody');
        var $empty = $('#emptyState');

        if (STATE.list.length === 0) {
            $tbody.empty();
            $empty.show();
            $('#paginationInfo').text('共 0 条数据');
            return;
        }

        $empty.hide();
        var html = '';
        $.each(STATE.list, function (i, item) {
            var badgeClass = getActionBadgeClass(item.action);
            html += '<tr>';
            html += '<td>' + item.id + '</td>';
            html += '<td style="font-size:12px;color:#64748b;">' + App.escapeHtml(item.created_at) + '</td>';
            html += '<td><code>' + App.escapeHtml(item.admin_name || '-') + '</code></td>';
            html += '<td><span class="label label-info">' + getModuleText(item.module) + '</span></td>';
            html += '<td><span class="label label-' + badgeClass + '">' + getActionText(item.action) + '</span></td>';
            html += '<td>' + App.escapeHtml(item.title || '-') + '</td>';
            html += '<td><code>' + App.escapeHtml(item.ip || '-') + '</code></td>';
            html += '</tr>';
        });

        $tbody.html(html);
        $('#paginationInfo').text('共 ' + STATE.total + ' 条，第 ' + STATE.page + '/' + Math.ceil(STATE.total / STATE.pageSize) + ' 页');
    }

    function renderPagination() {
        App.renderPagination($('#pagination'), STATE.total, STATE.page, STATE.pageSize, function (page) {
            STATE.page = page;
            loadList();
        });
    }

    function bindEvents() {
        $('#searchBtn').on('click', function () {
            STATE.keyword = $.trim($('#searchInput').val());
            STATE.module = $('#moduleFilter').val();
            STATE.action = $('#actionFilter').val();
            STATE.page = 1;
            loadList();
        });

        $('#searchInput').on('keypress', function (e) {
            if (e.which === 13) {
                $('#searchBtn').click();
            }
        });

        $('#refreshBtn').on('click', function () {
            loadList();
            App.toast('已刷新', 'success');
        });

        $('#jumpBtn').on('click', function () {
            var p = parseInt($('#jumpPage').val());
            var totalPages = Math.ceil(STATE.total / STATE.pageSize) || 1;
            if (p < 1 || p > totalPages) {
                App.toast('请输入有效的页码', 'warning');
                return;
            }
            STATE.page = p;
            loadList();
        });
    }

    $(function () {
        App.init(function () {
            loadDict();
            loadList();
            bindEvents();
        });
    });

})(jQuery);
