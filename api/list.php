<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AdminLog.php';

Auth::requirePermission('invitation:view');

try {
    $page      = max(1, (int)Utils::input('page', 1));
    $pageSize  = max(1, min(100, (int)Utils::input('page_size', 10)));
    $keyword   = trim((string)Utils::input('keyword', ''));
    $status    = (int)Utils::input('status', 0);

    $db       = Database::getInstance();
    $where    = array('`is_deleted` = 0');
    $params   = array();

    if ($keyword !== '') {
        $where[] = '(`code` LIKE :keyword OR `used_by` LIKE :keyword OR `remark` LIKE :keyword)';
        $params[':keyword'] = '%' . $keyword . '%';
    }

    if (Utils::isValidStatus($status)) {
        $where[] = '`status` = :status';
        $params[':status'] = $status;
    }

    $whereSql = implode(' AND ', $where);

    $totalSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE ' . $whereSql;
    $totalRow = $db->fetchOne($totalSql, $params);
    $total    = (int)$totalRow['total'];

    $offset = ($page - 1) * $pageSize;
    $listSql = sprintf(
        'SELECT `id`, `code`, `status`, `expire_at`, `used_by`, `used_at`, `used_ip`, `remark`, `created_at` 
         FROM `invitation_codes` 
         WHERE %s 
         ORDER BY `created_at` DESC 
         LIMIT %d, %d',
        $whereSql,
        $offset,
        $pageSize
    );

    $list = $db->fetchAll($listSql, $params);

    Utils::success('获取成功', array(
        'total'     => $total,
        'page'      => $page,
        'page_size' => $pageSize,
        'list'      => $list,
    ));
} catch (Exception $e) {
    Utils::error('获取失败: ' . $e->getMessage());
}
