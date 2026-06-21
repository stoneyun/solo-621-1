<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Utils.php';
require_once __DIR__ . '/../../includes/AdminLog.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::requirePermission('apikey:create');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::error('请求方式错误');
}

try {
    $name   = trim((string)Utils::input('name', ''));
    $apiKey = trim((string)Utils::input('api_key', ''));

    if (empty($name)) {
        Utils::error('请输入密钥名称');
    }
    if (strlen($name) > 64) {
        Utils::error('密钥名称不能超过64个字符');
    }

    $db = Database::getInstance();

    if (empty($apiKey)) {
        $apiKey = 'sk_inv_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 24);
    } else {
        if (strlen($apiKey) < 8 || strlen($apiKey) > 64) {
            Utils::error('API密钥长度需8-64个字符');
        }
    }

    $exists = $db->fetchOne(
        'SELECT id FROM `api_keys` WHERE `api_key` = :key AND `is_deleted` = 0 LIMIT 1',
        array(':key' => $apiKey)
    );
    if ($exists) {
        Utils::error('API密钥已存在，请更换');
    }

    $insertId = $db->insert('api_keys', array(
        'name'    => $name,
        'api_key' => $apiKey,
    ));

    AdminLog::record('apikey', 'create', '新增API密钥', array(
        'id'   => $insertId,
        'name' => $name,
    ));

    Utils::success('添加成功', array('id' => $insertId, 'name' => $name, 'api_key' => $apiKey));
} catch (Exception $e) {
    Utils::error('添加失败: ' . $e->getMessage());
}
