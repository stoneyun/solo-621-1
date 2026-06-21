<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Utils.php';
require_once __DIR__ . '/../../includes/AdminLog.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::requirePermission('apikey:delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::error('请求方式错误');
}

try {
    $id = (int)Utils::input('id', 0);

    if ($id <= 0) {
        Utils::error('无效的ID');
    }

    $db = Database::getInstance();

    $record = $db->fetchOne(
        'SELECT * FROM `api_keys` WHERE `id` = :id AND `is_deleted` = 0 LIMIT 1',
        array(':id' => $id)
    );
    if (!$record) {
        Utils::error('记录不存在或已删除');
    }

    $affected = $db->update(
        'api_keys',
        array('is_deleted' => 1),
        '`id` = :where_id',
        array(':where_id' => $id)
    );

    AdminLog::record('apikey', 'delete', '删除API密钥', array(
        'id'   => $id,
        'name' => $record['name'],
    ));

    Utils::success('删除成功', array('affected' => $affected));
} catch (Exception $e) {
    Utils::error('删除失败: ' . $e->getMessage());
}
