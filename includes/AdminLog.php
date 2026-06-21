<?php
/**
 * 操作日志类
 */
class AdminLog
{
    /**
     * 记录操作日志
     *
     * @param string $module 模块
     * @param string $action 操作动作
     * @param string $title 标题
     * @param array|mixed $content 内容
     */
    public static function record($module, $action, $title, $content = array())
    {
        try {
            $db = Database::getInstance();

            $adminId = 0;
            $adminName = '';
            if (class_exists('Auth', false) && Auth::check()) {
                $adminId = Auth::id();
                $adminName = Auth::username();
            }

            $contentJson = is_array($content) || is_object($content)
                ? json_encode($content, JSON_UNESCAPED_UNICODE)
                : (string)$content;

            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';

            $db->insert('admin_logs', array(
                'admin_id'   => $adminId > 0 ? $adminId : null,
                'admin_name' => $adminName ?: null,
                'module'     => $module,
                'action'     => $action,
                'title'      => $title,
                'content'    => $contentJson,
                'ip'         => $ip,
                'user_agent' => $userAgent,
            ));
        } catch (Exception $e) {
        }
    }

    /**
     * 分页获取日志列表
     *
     * @param int $page
     * @param int $pageSize
     * @param array $filters
     * @return array
     */
    public static function getList($page = 1, $pageSize = 20, $filters = array())
    {
        $db = Database::getInstance();

        $where = array('1 = 1');
        $params = array();

        if (!empty($filters['module'])) {
            $where[] = '`module` = :module';
            $params[':module'] = $filters['module'];
        }

        if (!empty($filters['action'])) {
            $where[] = '`action` = :action';
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['admin_id'])) {
            $where[] = '`admin_id` = :admin_id';
            $params[':admin_id'] = (int)$filters['admin_id'];
        }

        if (!empty($filters['keyword'])) {
            $where[] = '(`title` LIKE :keyword OR `admin_name` LIKE :keyword)';
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
        }

        $whereSql = implode(' AND ', $where);

        $totalSql = 'SELECT COUNT(*) AS total FROM `admin_logs` WHERE ' . $whereSql;
        $totalRow = $db->fetchOne($totalSql, $params);
        $total = (int)$totalRow['total'];

        $offset = max(0, ($page - 1) * $pageSize);
        $listSql = sprintf(
            'SELECT * FROM `admin_logs` WHERE %s ORDER BY `id` DESC LIMIT %d, %d',
            $whereSql,
            $offset,
            (int)$pageSize
        );

        $list = $db->fetchAll($listSql, $params);

        return array(
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
            'list'      => $list,
        );
    }

    /**
     * 获取模块列表
     *
     * @return array
     */
    public static function getModules()
    {
        return array(
            'invitation' => '邀请码',
            'apikey'     => 'API密钥',
            'admin'      => '管理员',
            'group'      => '分组权限',
            'log'        => '操作日志',
            'auth'       => '登录认证',
        );
    }

    /**
     * 获取动作类型
     *
     * @return array
     */
    public static function getActions()
    {
        return array(
            'login'      => '登录',
            'logout'     => '登出',
            'create'     => '新增',
            'update'     => '更新',
            'delete'     => '删除',
            'batch'      => '批量操作',
            'login_fail' => '登录失败',
            'verify'     => '核销',
            'export'     => '导出',
        );
    }
}
