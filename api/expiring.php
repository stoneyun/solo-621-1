<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AdminLog.php';

Auth::requirePermission('invitation:view');

try {
    $days = max(1, min(90, (int)Utils::input('days', 7)));
    $now  = date('Y-m-d H:i:s');
    $expiringAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));

    $db = Database::getInstance();

    $now2 = date('Y-m-d H:i:s');
    $alreadyExpired = $db->fetchOne(
        'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 1 AND `expire_at` <= :now',
        array(':now' => $now2)
    );
    if ($alreadyExpired && $alreadyExpired['total'] > 0) {
        $db->query(
            'UPDATE `invitation_codes` SET `status` = 3 WHERE `is_deleted` = 0 AND `status` = 1 AND `expire_at` <= :now',
            array(':now' => $now2)
        );
    }

    $sql = 'SELECT `id`, `code`, `status`, `expire_at`, `remark`, `created_at` 
            FROM `invitation_codes` 
            WHERE `is_deleted` = 0 AND `status` = 1 AND `expire_at` <= :expiring_at AND `expire_at` > :now 
            ORDER BY `expire_at` ASC 
            LIMIT 200';

    $list = $db->fetchAll($sql, array(
        ':expiring_at' => $expiringAt,
        ':now'         => $now,
    ));

    foreach ($list as &$item) {
        $expireTime = strtotime($item['expire_at']);
        $diff = $expireTime - time();
        $item['remaining_hours'] = max(0, round($diff / 3600, 1));
        $item['remaining_text'] = '';
        if ($diff > 86400) {
            $item['remaining_text'] = floor($diff / 86400) . '天后';
        } elseif ($diff > 3600) {
            $item['remaining_text'] = floor($diff / 3600) . '小时后';
        } else {
            $item['remaining_text'] = max(1, ceil($diff / 60)) . '分钟后';
        }
    }
    unset($item);

    $totalExpiring = count($list);

    Utils::success('获取成功', array(
        'days'    => $days,
        'total'   => $totalExpiring,
        'list'    => $list,
    ));
} catch (Exception $e) {
    Utils::error('获取过期提醒失败: ' . $e->getMessage());
}
