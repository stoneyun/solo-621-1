<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AdminLog.php';

Auth::requirePermission('invitation:view');

try {
    $keyword       = trim((string)Utils::input('keyword', ''));
    $status        = (int)Utils::input('status', 0);
    $expiringOnly  = (int)Utils::input('expiring_only', 0);
    $expiringDays  = max(1, (int)Utils::input('expiring_days', 7));

    $db    = Database::getInstance();
    $where = array('`is_deleted` = 0');
    $params = array();

    if ($keyword !== '') {
        $where[] = '(`code` LIKE :keyword OR `used_by` LIKE :keyword OR `remark` LIKE :keyword)';
        $params[':keyword'] = '%' . $keyword . '%';
    }

    if (Utils::isValidStatus($status)) {
        $where[] = '`status` = :status';
        $params[':status'] = $status;
    }

    if ($expiringOnly) {
        $now = date('Y-m-d H:i:s');
        $expiringAt = date('Y-m-d H:i:s', strtotime("+{$expiringDays} days"));
        $where[] = '`status` = 1 AND `expire_at` <= :expiring_at AND `expire_at` > :now';
        $params[':expiring_at'] = $expiringAt;
        $params[':now'] = $now;
    }

    $whereSql = implode(' AND ', $where);

    $sql = 'SELECT `id`, `code`, `status`, `expire_at`, `used_by`, `used_at`, `used_ip`, `remark`, `created_at` 
            FROM `invitation_codes` 
            WHERE ' . $whereSql . ' 
            ORDER BY `created_at` DESC 
            LIMIT 50000';

    $list = $db->fetchAll($sql, $params);

    $statusMap = array(1 => '未使用', 2 => '已使用', 3 => '已过期');
    $filename = '邀请码列表_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, array('ID', '邀请码', '状态', '有效期', '使用人', '使用时间', '使用IP', '备注', '创建时间'));

    foreach ($list as $row) {
        fputcsv($output, array(
            $row['id'],
            $row['code'],
            isset($statusMap[$row['status']]) ? $statusMap[$row['status']] : '未知',
            $row['expire_at'],
            $row['used_by'] ?: '',
            $row['used_at'] ?: '',
            $row['used_ip'] ?: '',
            $row['remark'] ?: '',
            $row['created_at'],
        ));
    }

    fclose($output);

    AdminLog::record('invitation', 'export', '导出邀请码列表', array(
        'count' => count($list),
        'filters' => array(
            'keyword' => $keyword,
            'status' => $status,
            'expiring_only' => $expiringOnly,
        ),
    ));
} catch (Exception $e) {
    Utils::error('导出失败: ' . $e->getMessage());
}
