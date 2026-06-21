<?php
define('IN_SYSTEM', true);
$page_title = 'API密钥管理';
$current_page = 'apikey';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Utils.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/layout/header.php';
?>
<div class="app-card">
    <div class="card-header">
        <h3><i class="fa fa-key" style="margin-right:6px;color:#1e3a8a;"></i>API密钥管理</h3>
    </div>

    <div class="alert alert-info" style="margin-bottom:20px;padding:12px 16px;background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;color:#1e40af;">
        <i class="fa fa-info-circle" style="margin-right:6px;"></i>
        API密钥用于外部业务系统调用核销接口，请妥善保管，泄露后请及时删除并重新生成。
    </div>

    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-input-group">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="搜索名称/密钥..." value="">
            </div>
            <button class="btn btn-default btn-sm" id="searchBtn">
                <i class="fa fa-filter"></i> 筛选
            </button>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary btn-sm" id="createBtn" data-permission="apikey:create">
                <i class="fa fa-plus"></i> 添加密钥
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="dataTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>API密钥</th>
                    <th>创建时间</th>
                    <th style="width:180px;">操作</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            </tbody>
        </table>
    </div>

    <div id="emptyState" class="empty-state" style="display:none;">
        <div class="empty-icon"><i class="fa fa-key"></i></div>
        <p>暂无API密钥</p>
    </div>

    <div class="pagination-wrap">
        <div class="pagination-info" id="paginationInfo"></div>
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
            <ul class="pagination" id="pagination"></ul>
            <div class="page-jump">
                <span>跳至</span>
                <input type="number" id="jumpPage" class="form-control" min="1" value="1">
                <span>页</span>
                <button class="btn btn-default btn-sm" id="jumpBtn">GO</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-plus-circle" style="color:#1e3a8a;"></i> 添加API密钥</h4>
            </div>
            <div class="modal-body">
                <form id="createForm">
                    <div class="form-group">
                        <label>密钥名称 <span class="required">*</span></label>
                        <input type="text" class="form-control" id="createName" maxlength="64" placeholder="请输入密钥名称，如：业务系统核销">
                    </div>
                    <div class="form-group">
                        <label>自定义密钥</label>
                        <input type="text" class="form-control" id="createApiKey" maxlength="64" placeholder="留空则自动生成">
                        <span class="help-block">留空将自动生成随机密钥，自定义密钥需8-64个字符</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="submitCreate">确认添加</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="confirmTitle"><i class="fa fa-question-circle" style="color:#ef4444;"></i> 确认操作</h4>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmOk">确定</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/apikeys.js"></script>
<?php
require_once __DIR__ . '/includes/layout/footer.php';
