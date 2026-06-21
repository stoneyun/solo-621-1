<?php
/**
 * 认证与权限类
 * 处理登录、Session管理、权限检查
 */
class Auth
{
    const SESSION_KEY = 'admin_user';
    const SESSION_EXPIRE = 7200;

    private static $currentUser = null;

    /**
     * 初始化Session
     */
    public static function init()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 检查是否已登录
     *
     * @return bool
     */
    public static function check()
    {
        self::init();
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        $user = $_SESSION[self::SESSION_KEY];
        if (!isset($user['id']) || !isset($user['login_time'])) {
            return false;
        }

        if (time() - $user['login_time'] > self::SESSION_EXPIRE) {
            self::logout();
            return false;
        }

        self::$currentUser = $user;
        return true;
    }

    /**
     * 获取当前登录用户
     *
     * @return array|null
     */
    public static function user()
    {
        if (self::$currentUser === null) {
            self::check();
        }
        return self::$currentUser;
    }

    /**
     * 获取当前用户ID
     *
     * @return int
     */
    public static function id()
    {
        $user = self::user();
        return $user ? (int)$user['id'] : 0;
    }

    /**
     * 获取当前用户名
     *
     * @return string
     */
    public static function username()
    {
        $user = self::user();
        return $user ? $user['username'] : '';
    }

    /**
     * 是否超级管理员
     *
     * @return bool
     */
    public static function isSuper()
    {
        $user = self::user();
        return $user && !empty($user['is_super']);
    }

    /**
     * 登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool|string 成功返回true，失败返回错误消息
     */
    public static function login($username, $password)
    {
        self::init();

        if (empty($username) || empty($password)) {
            return '用户名和密码不能为空';
        }

        $db = Database::getInstance();
        $admin = $db->fetchOne(
            'SELECT * FROM `admins` WHERE `username` = :username AND `is_deleted` = 0 LIMIT 1',
            array(':username' => $username)
        );

        if (!$admin) {
            return '用户名或密码错误';
        }

        if ($admin['status'] != 1) {
            return '账号已被禁用，请联系管理员';
        }

        if (!self::verifyPassword($password, $admin['password'])) {
            self::updateLoginFail($admin['id']);
            AdminLog::record('admin', 'login_fail', '登录失败', array('username' => $username, 'reason' => '密码错误'));
            return '用户名或密码错误';
        }

        $group = null;
        if (!empty($admin['group_id'])) {
            $group = $db->fetchOne(
                'SELECT * FROM `admin_groups` WHERE `id` = :id AND `is_deleted` = 0 LIMIT 1',
                array(':id' => $admin['group_id'])
            );
        }

        $permissions = array();
        if ($admin['is_super']) {
            $permissions = self::getAllPermissions();
        } elseif ($group && !empty($group['permissions'])) {
            $permissions = json_decode($group['permissions'], true);
            if (!is_array($permissions)) {
                $permissions = array();
            }
        }

        $user = array(
            'id'         => (int)$admin['id'],
            'username'   => $admin['username'],
            'real_name'  => $admin['real_name'],
            'email'      => $admin['email'],
            'group_id'   => (int)$admin['group_id'],
            'group_name' => $group ? $group['name'] : '',
            'is_super'   => (bool)$admin['is_super'],
            'status'     => (int)$admin['status'],
            'permissions'=> $permissions,
            'login_time' => time(),
            'login_ip'   => self::getClientIp(),
        );

        $_SESSION[self::SESSION_KEY] = $user;
        self::$currentUser = $user;

        self::updateLoginSuccess($admin['id']);
        AdminLog::record('admin', 'login', '登录系统', array('username' => $username));

        return true;
    }

    /**
     * 登出
     */
    public static function logout()
    {
        self::init();
        $user = self::user();
        if ($user) {
            AdminLog::record('admin', 'logout', '退出登录', array('username' => $user['username']));
        }
        unset($_SESSION[self::SESSION_KEY]);
        self::$currentUser = null;
        session_destroy();
    }

    /**
     * 检查权限
     *
     * @param string $permission 权限标识 如 invitation:create
     * @return bool
     */
    public static function hasPermission($permission)
    {
        if (self::isSuper()) {
            return true;
        }

        $user = self::user();
        if (!$user || empty($user['permissions'])) {
            return false;
        }

        return in_array($permission, $user['permissions']);
    }

    /**
     * 权限检查，不通过则输出错误
     *
     * @param string $permission
     */
    public static function requirePermission($permission)
    {
        if (!self::check()) {
            Utils::jsonResponse(401, '请先登录');
        }
        if (!self::hasPermission($permission)) {
            Utils::jsonResponse(403, '没有操作权限');
        }
    }

    /**
     * 验证密码
     *
     * @param string $password 明文密码
     * @param string $hash 哈希值
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        if (function_exists('password_verify')) {
            return password_verify($password, $hash);
        }
        return self::compatPasswordVerify($password, $hash);
    }

    /**
     * 加密密码
     *
     * @param string $password 明文密码
     * @return string
     */
    public static function hashPassword($password)
    {
        if (function_exists('password_hash')) {
            return password_hash($password, PASSWORD_DEFAULT);
        }
        return self::compatPasswordHash($password);
    }

    /**
     * 兼容密码哈希（PHP 5.6 以下）
     */
    private static function compatPasswordHash($password)
    {
        $salt = '$2y$10$' . substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
        return crypt($password, $salt);
    }

    private static function compatPasswordVerify($password, $hash)
    {
        if (substr($hash, 0, 4) == '$2y$') {
            $hash2 = crypt($password, $hash);
            return $hash2 === $hash;
        }
        return md5($password) === $hash;
    }

    /**
     * 更新登录成功信息
     */
    private static function updateLoginSuccess($adminId)
    {
        $db = Database::getInstance();
        $ip = self::getClientIp();
        $db->query(
            'UPDATE `admins` SET `last_login_ip` = :ip, `last_login_at` = NOW(), `login_count` = `login_count` + 1 WHERE `id` = :id',
            array(':ip' => $ip, ':id' => $adminId)
        );
    }

    private static function updateLoginFail($adminId)
    {
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    public static function getClientIp()
    {
        $ip = '0.0.0.0';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return trim($ip);
    }

    /**
     * 获取所有权限清单
     *
     * @return array
     */
    public static function getAllPermissions()
    {
        return array(
            'invitation:view',
            'invitation:create',
            'invitation:edit',
            'invitation:delete',
            'invitation:batch',
            'apikey:view',
            'apikey:create',
            'apikey:delete',
            'admin:view',
            'admin:create',
            'admin:edit',
            'admin:delete',
            'group:view',
            'group:create',
            'group:edit',
            'group:delete',
            'log:view',
        );
    }

    /**
     * 获取权限分组（用于前端展示）
     *
     * @return array
     */
    public static function getPermissionGroups()
    {
        $groups = self::getAllPermissionGroups();
        $result = array();
        foreach ($groups as $key => $group) {
            $perms = array();
            foreach ($group['items'] as $permKey => $permName) {
                $perms[] = array(
                    'key'  => $permKey,
                    'name' => $permName,
                );
            }
            $result[] = array(
                'key'         => $key,
                'name'        => $group['label'],
                'permissions' => $perms,
            );
        }
        return $result;
    }

    /**
     * 获取所有权限分组（内部原始格式）
     *
     * @return array
     */
    private static function getAllPermissionGroups()
    {
        return array(
            'invitation' => array(
                'label' => '邀请码管理',
                'items' => array(
                    'invitation:view'   => '查看邀请码',
                    'invitation:create' => '添加邀请码',
                    'invitation:edit'   => '编辑邀请码',
                    'invitation:delete' => '删除邀请码',
                    'invitation:batch'  => '批量操作',
                ),
            ),
            'apikey' => array(
                'label' => 'API密钥管理',
                'items' => array(
                    'apikey:view'   => '查看API密钥',
                    'apikey:create' => '添加API密钥',
                    'apikey:delete' => '删除API密钥',
                ),
            ),
            'admin' => array(
                'label' => '管理员管理',
                'items' => array(
                    'admin:view'   => '查看管理员',
                    'admin:create' => '添加管理员',
                    'admin:edit'   => '编辑管理员',
                    'admin:delete' => '删除管理员',
                ),
            ),
            'group' => array(
                'label' => '分组权限',
                'items' => array(
                    'group:view'   => '查看分组',
                    'group:create' => '添加分组',
                    'group:edit'   => '编辑分组',
                    'group:delete' => '删除分组',
                ),
            ),
            'log' => array(
                'label' => '操作日志',
                'items' => array(
                    'log:view' => '查看日志',
                ),
            ),
        );
    }
}
