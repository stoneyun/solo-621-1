<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AdminLog.php';

Auth::requirePermission('invitation:view');

try {
    $db = Database::getInstance();

    $today = date('Y-m-d');
    $now   = date('Y-m-d H:i:s');
    $expiringDays = (int)Utils::input('expiring_days', 7);
    if ($expiringDays < 1) $expiringDays = 7;
    $expiringAt = date('Y-m-d H:i:s', strtotime("+{$expiringDays} days"));

    $totalSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0';
    $totalRow = $db->fetchOne($totalSql);
    $total = (int)$totalRow['total'];

    $unusedSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 1';
    $unusedRow = $db->fetchOne($unusedSql);
    $unused = (int)$unusedRow['total'];

    $usedSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 2';
    $usedRow = $db->fetchOne($usedSql);
    $used = (int)$usedRow['total'];

    $expiredSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 3';
    $expiredRow = $db->fetchOne($expiredSql);
    $expired = (int)$expiredRow['total'];

    $todayNewSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `created_at` >= :today_start';
    $todayNewRow = $db->fetchOne($todayNewSql, array(':today_start' => $today . ' 00:00:00'));
    $todayNew = (int)$todayNewRow['total'];

    $todayUsedSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 2 AND `used_at` >= :today_start';
    $todayUsedRow = $db->fetchOne($todayUsedSql, array(':today_start' => $today . ' 00:00:00'));
    $todayUsed = (int)$todayUsedRow['total'];

    $expiringSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 1 AND `expire_at` <= :expiring_at AND `expire_at` > :now';
    $expiringRow = $db->fetchOne($expiringSql, array(':expiring_at' => $expiringAt, ':now' => $now));
    $expiring = (int)$expiringRow['total'];

    $alreadyExpiredSql = 'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 1 AND `expire_at` <= :now';
    $alreadyExpiredRow = $db->fetchOne($alreadyExpiredSql, array(':now' => $now));
    $alreadyExpired = (int)$alreadyExpiredRow['total'];

    if ($alreadyExpired > 0) {
        $db->query(
            'UPDATE `invitation_codes` SET `status` = 3 WHERE `is_deleted` = 0 AND `status` = 1 AND `expire_at` <= :now',
            array(':now' => $now)
        );
    }

    $conversionRate = $total > 0 ? round(($used / $total) * 100, 2) : 0;

    $last7Days = array();
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dayStart = $date . ' 00:00:00';
        $dayEnd   = $date . ' 23:59:59';

        $dayNew = $db->fetchOne(
            'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `created_at` >= :start AND `created_at` <= :end',
            array(':start' => $dayStart, ':end' => $dayEnd)
        );
        $dayUsed = $db->fetchOne(
            'SELECT COUNT(*) AS total FROM `invitation_codes` WHERE `is_deleted` = 0 AND `status` = 2 AND `used_at` >= :start AND `used_at` <= :end',
            array(':start' => $dayStart, ':end' => $dayEnd)
        );

        $last7Days[] = array(
            'date'  => $date,
            'new'   => (int)$dayNew['total'],
            'used'  => (int)$dayUsed['total'],
        );
    }

    Utils::success('获取成功', array(
        'total'          => $total,
        'unused'         => $unused,
        'used'           => $used,
        'expired'        => $expired,
        'today_new'      => $todayNew,
        'today_used'     => $todayUsed,
        'expiring_soon'  => $expiring,
        'conversion_rate'=> $conversionRate,
        'last_7_days'    => $last7Days,
    ));
} catch (Exception $e) {
    Utils::error('获取统计失败: ' . $e->getMessage());
}
