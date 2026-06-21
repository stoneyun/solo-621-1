<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Utils.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/AdminLog.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::jsonResponse(405, '仅支持POST请求');
}

$apiKey = '';
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    $key = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';
    $keyLower = isset($headers['x-api-key']) ? $headers['x-api-key'] : '';
    $apiKey = trim($key ?: $keyLower);
}

if (empty($apiKey)) {
    $apiKey = trim((string)Utils::input('api_key', ''));
}

if (empty($apiKey)) {
    Utils::jsonResponse(401, '缺少API密钥，请在Header中传入X-API-Key或参数api_key');
}

try {
    $db = Database::getInstance();

    $keyRecord = $db->fetchOne(
        'SELECT id, name FROM `api_keys` WHERE `api_key` = :key AND `is_deleted` = 0 LIMIT 1',
        array(':key' => $apiKey)
    );
    if (!$keyRecord) {
        Utils::jsonResponse(401, '无效的API密钥');
    }
} catch (Exception $e) {
    Utils::jsonResponse(500, '服务内部错误');
}

$code   = trim((string)Utils::input('code', ''));
$usedBy = trim((string)Utils::input('used_by', ''));

if (empty($code)) {
    Utils::jsonResponse(400, '缺少邀请码参数(code)');
}
if (empty($usedBy)) {
    Utils::jsonResponse(400, '缺少使用人参数(used_by)');
}
if (strlen($usedBy) > 64) {
    Utils::jsonResponse(400, '使用人信息不能超过64个字符');
}

try {
    $db = Database::getInstance();

    $record = $db->fetchOne(
        'SELECT * FROM `invitation_codes` WHERE `code` = :code AND `is_deleted` = 0 LIMIT 1',
        array(':code' => $code)
    );

    if (!$record) {
        AdminLog::record('invitation', 'verify', '核销失败-邀请码不存在', array(
            'code' => $code,
            'used_by' => $usedBy,
        ));
        Utils::jsonResponse(404, '邀请码不存在');
    }

    if ($record['status'] == 2) {
        Utils::jsonResponse(40009, '邀请码已被使用', array(
            'code'    => $record['code'],
            'used_by' => $record['used_by'],
            'used_at' => $record['used_at'],
        ));
    }

    if ($record['status'] == 3) {
        AdminLog::record('invitation', 'verify', '核销失败-邀请码已过期', array(
            'code' => $code,
            'expire_at' => $record['expire_at'],
        ));
        Utils::jsonResponse(40008, '邀请码已过期', array(
            'code'      => $record['code'],
            'expire_at' => $record['expire_at'],
        ));
    }

    $now = date('Y-m-d H:i:s');
    $ip  = Auth::getClientIp();

    if (strtotime($record['expire_at']) < strtotime($now)) {
        $db->update(
            'invitation_codes',
            array('status' => 3),
            '`id` = :where_id',
            array(':where_id' => $record['id'])
        );
        AdminLog::record('invitation', 'verify', '核销失败-邀请码已过期', array(
            'code' => $code,
            'expire_at' => $record['expire_at'],
        ));
        Utils::jsonResponse(40008, '邀请码已过期', array(
            'code'      => $record['code'],
            'expire_at' => $record['expire_at'],
        ));
    }

    $updateData = array(
        'status'  => 2,
        'used_by' => $usedBy,
        'used_at' => $now,
        'used_ip' => $ip,
    );

    $affected = $db->update(
        'invitation_codes',
        $updateData,
        '`id` = :where_id AND `status` = 1',
        array(':where_id' => $record['id'])
    );

    if ($affected <= 0) {
        Utils::jsonResponse(409, '核销失败，邀请码状态可能已变更，请重试');
    }

    AdminLog::record('invitation', 'verify', '邀请码核销成功', array(
        'id'      => $record['id'],
        'code'    => $code,
        'used_by' => $usedBy,
        'used_at' => $now,
        'used_ip' => $ip,
    ));

    Utils::success('核销成功', array(
        'id'        => $record['id'],
        'code'      => $record['code'],
        'used_by'   => $usedBy,
        'used_at'   => $now,
        'expire_at' => $record['expire_at'],
    ));
} catch (Exception $e) {
    Utils::jsonResponse(500, '核销服务异常: ' . $e->getMessage());
}
