<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AdminLog.php';

Auth::requirePermission('invitation:view');

try {
    $id = (int)Utils::input('id', 0);
    $code = trim((string)Utils::input('code', ''));

    if ($id <= 0 && $code === '') {
        Utils::error('缺少参数：id 或 code');
    }

    $db = Database::getInstance();

    $where = '';
    $params = array();

    if ($id > 0) {
        $where = '`id` = :id';
        $params[':id'] = $id;
    } else {
        $where = '`code` = :code';
        $params[':code'] = $code;
    }

    $record = $db->fetchOne(
        'SELECT * FROM `invitation_codes` WHERE ' . $where . ' AND `is_deleted` = 0 LIMIT 1',
        $params
    );

    if (!$record) {
        Utils::error('邀请码不存在');
    }

    $logSql = 'SELECT `id`, `module`, `action`, `title`, `content`, `ip`, `admin_name`, `created_at`
              FROM `admin_logs`
              WHERE `module` = \'invitation\'
              AND (`content` LIKE :code_cond1 OR `title` LIKE :code_cond2)
              ORDER BY `id` DESC
              LIMIT 50';

    $logParams = array(
        ':code_cond1' => '%"code":"' . $record['code'] . '"%',
        ':code_cond2' => '%' . $record['code'] . '%',
    );

    $logs = $db->fetchAll($logSql, $logParams);

    $statusMap = array(1 => '未使用', 2 => '已使用', 3 => '已过期');

    $detail = array(
        'id'         => (int)$record['id'],
        'code'       => $record['code'],
        'status'     => (int)$record['status'],
        'status_text'=> isset($statusMap[$record['status']]) ? $statusMap[$record['status']] : '未知',
        'expire_at'  => $record['expire_at'],
        'used_by'    => $record['used_by'],
        'used_at'    => $record['used_at'],
        'used_ip'    => $record['used_ip'],
        'remark'     => $record['remark'],
        'created_at' => $record['created_at'],
        'updated_at' => $record['updated_at'],
    );

    $expireTime = strtotime($record['expire_at']);
    $now = time();
    if ($record['status'] == 1) {
        if ($expireTime > $now) {
            $diff = $expireTime - $now;
            $detail['remaining_seconds'] = $diff;
            if ($diff > 86400) {
                $detail['remaining_text'] = floor($diff / 86400) . '天' . floor(($diff % 86400) / 3600) . '小时';
            } elseif ($diff > 3600) {
                $detail['remaining_text'] = floor($diff / 3600) . '小时' . floor(($diff % 3600) / 60) . '分钟';
            } else {
                $detail['remaining_text'] = floor($diff / 60) . '分钟';
            }
        } else {
            $detail['remaining_text'] = '已过期';
        }
    } else {
        $detail['remaining_text'] = '-';
    }

    foreach ($logs as &$log) {
        $log['content_arr'] = json_decode($log['content'], true);
    }
    unset($log);

    Utils::success('获取成功', array(
        'detail' => $detail,
        'logs'   => $logs,
    ));
} catch (Exception $e) {
    Utils::error('获取失败: ' . $e->getMessage());
}
