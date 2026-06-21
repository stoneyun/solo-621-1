<?php
define('IN_SYSTEM', true);
$page_title = '邀请码管理';
$current_page = 'invitation';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Utils.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/layout/header.php';
?>
<div class="stats-row" id="statsRow">
    <div class="stat-card stat-total">
        <div class="stat-icon"><i class="fa fa-ticket"></i></div>
        <div class="stat-body">
            <div class="stat-value" id="statTotal">-</div>
            <div class="stat-label">邀请码总数</div>
        </div>
    </div>
    <div class="stat-card stat-today-new">
        <div class="stat-icon"><i class="fa fa-plus-circle"></i></div>
        <div class="stat-body">
            <div class="stat-value" id="statTodayNew">-</div>
            <div class="stat-label">今日新增</div>
        </div>
    </div>
    <div class="stat-card stat-today-used">
        <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
        <div class="stat-body">
            <div class="stat-value" id="statTodayUsed">-</div>
            <div class="stat-label">今日已使用</div>
        </div>
    </div>
    <div class="stat-card stat-expiring">
        <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
        <div class="stat-body">
            <div class="stat-value" id="statExpiring">-</div>
            <div class="stat-label">即将过期(7天内)</div>
        </div>
    </div>
    <div class="stat-card stat-conversion">
        <div class="stat-icon"><i class="fa fa-line-chart"></i></div>
        <div class="stat-body">
            <div class="stat-value" id="statConversion">-</div>
            <div class="stat-label">转化率</div>
        </div>
    </div>
</div>

<div class="expiring-alert" id="expiringAlert" style="display:none;">
    <div class="alert-header">
        <i class="fa fa-bell"></i> 过期提醒 <span class="alert-badge" id="expiringCount">0</span>个邀请码即将过期
        <button class="alert-toggle" id="toggleExpiring"><i class="fa fa-chevron-down"></i></button>
    </div>
    <div class="alert-body" id="expiringBody" style="display:none;">
        <div class="expiring-list" id="expiringList"></div>
    </div>
</div>

<div class="app-card">
    <div class="card-header">
        <h3><i class="fa fa-list-alt" style="margin-right:6px;color:#1e3a8a;"></i>邀请码列表</h3>
    </div>

    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-input-group">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="搜索邀请码/使用人/备注..." value="">
            </div>
            <select id="statusFilter" class="form-control" style="width:140px;">
                <option value="0">全部状态</option>
                <option value="1">未使用</option>
                <option value="2">已使用</option>
                <option value="3">已过期</option>
            </select>
            <button class="btn btn-default btn-sm" id="searchBtn">
                <i class="fa fa-filter"></i> 筛选
            </button>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-success btn-sm" id="exportBtn" data-permission="invitation:view">
                <i class="fa fa-download"></i> 导出CSV
            </button>
            <button class="btn btn-danger btn-sm" id="batchDeleteBtn" data-permission="invitation:delete">
                <i class="fa fa-trash-o"></i> 批量删除
            </button>
            <button class="btn btn-warning btn-sm" id="batchCreateBtn" data-permission="invitation:create">
                <i class="fa fa-cubes"></i> 批量生成
            </button>
            <button class="btn btn-primary btn-sm" id="createBtn" data-permission="invitation:create">
                <i class="fa fa-plus"></i> 添加邀请码
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="dataTable">
            <thead>
                <tr>
                    <th class="checkbox-column">
                        <input type="checkbox" id="checkAll">
                    </th>
                    <th>邀请码</th>
                    <th>状态</th>
                    <th>有效期</th>
                    <th>使用人</th>
                    <th>使用时间</th>
                    <th>核销IP</th>
                    <th>备注</th>
                    <th>创建时间</th>
                    <th style="width:200px;">操作</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            </tbody>
        </table>
    </div>

    <div id="emptyState" class="empty-state" style="display:none;">
        <div class="empty-icon"><i class="fa fa-inbox"></i></div>
        <p>暂无数据</p>
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

<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-info-circle" style="color:#1e3a8a;"></i> 邀请码详情</h4>
            </div>
            <div class="modal-body">
                <div class="detail-section">
                    <h5 class="detail-section-title"><i class="fa fa-info-circle"></i> 基本信息</h5>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">邀请码</span>
                            <span class="detail-value" id="detailCode">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">状态</span>
                            <span class="detail-value" id="detailStatus">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">有效期</span>
                            <span class="detail-value" id="detailExpireAt">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">剩余时间</span>
                            <span class="detail-value" id="detailRemaining">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">使用人</span>
                            <span class="detail-value" id="detailUsedBy">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">使用时间</span>
                            <span class="detail-value" id="detailUsedAt">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">核销IP</span>
                            <span class="detail-value" id="detailUsedIp">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">创建时间</span>
                            <span class="detail-value" id="detailCreatedAt">-</span>
                        </div>
                        <div class="detail-item detail-item-full">
                            <span class="detail-label">备注</span>
                            <span class="detail-value" id="detailRemark">-</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h5 class="detail-section-title"><i class="fa fa-history"></i> 操作日志</h5>
                    <div class="detail-log-list" id="detailLogList">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-plus-circle" style="color:#1e3a8a;"></i> 添加邀请码</h4>
            </div>
            <div class="modal-body">
                <form id="createForm">
                    <div class="auto-generate-wrap">
                        <label class="switch-label">
                            <input type="checkbox" id="autoGenerate" checked>
                            自动生成邀请码
                        </label>
                    </div>
                    <div class="form-group" id="codeGroup" style="display:none;">
                        <label>邀请码 <span class="required">*</span></label>
                        <input type="text" class="form-control" id="createCode" maxlength="32" placeholder="请输入自定义邀请码">
                        <span class="help-block">自定义邀请码，最多32个字符</span>
                    </div>
                    <div class="form-group">
                        <label>有效期 <span class="required">*</span></label>
                        <input type="datetime-local" class="form-control" id="createExpireAt">
                    </div>
                    <div class="form-group">
                        <label>备注</label>
                        <textarea class="form-control" id="createRemark" rows="2" maxlength="255" placeholder="选填"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="submitCreate">确定添加</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-pencil-square-o" style="color:#f59e0b;"></i> 编辑邀请码</h4>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editId">
                    <div class="form-group">
                        <label>邀请码</label>
                        <input type="text" class="form-control" id="editCode" readonly style="background-color:#f8fafc;">
                    </div>
                    <div class="form-group">
                        <label>状态 <span class="required">*</span></label>
                        <select class="form-control" id="editStatus">
                            <option value="1">未使用</option>
                            <option value="2">已使用</option>
                            <option value="3">已过期</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>有效期 <span class="required">*</span></label>
                        <input type="datetime-local" class="form-control" id="editExpireAt">
                    </div>
                    <div class="form-group" id="editUsedByGroup">
                        <label id="editUsedByLabel">使用人</label>
                        <input type="text" class="form-control" id="editUsedBy" maxlength="64" placeholder="状态为已使用时必填">
                    </div>
                    <div class="form-group">
                        <label>备注</label>
                        <textarea class="form-control" id="editRemark" rows="2" maxlength="255" placeholder="选填"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="submitEdit" data-permission="invitation:edit">保存修改</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="batchCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-cubes" style="color:#f59e0b;"></i> 批量生成邀请码</h4>
            </div>
            <div class="modal-body">
                <form id="batchCreateForm">
                    <div class="form-group">
                        <label>生成数量 <span class="required">*</span></label>
                        <input type="number" class="form-control" id="batchCount" min="1" max="1000" value="10" placeholder="1-1000">
                        <span class="help-block">单次最多生成1000个</span>
                    </div>
                    <div class="form-group">
                        <label>统一有效期 <span class="required">*</span></label>
                        <input type="datetime-local" class="form-control" id="batchExpireAt">
                    </div>
                    <div class="form-group">
                        <label>统一备注</label>
                        <textarea class="form-control" id="batchRemark" rows="2" maxlength="255" placeholder="选填，所有邀请码使用同一备注"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-warning" id="submitBatchCreate">开始生成</button>
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

<script src="assets/js/app.js"></script>
<?php
require_once __DIR__ . '/includes/layout/footer.php';
