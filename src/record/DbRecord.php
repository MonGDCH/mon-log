<?php

declare(strict_types=1);

namespace mon\log\record;

use PDO;
use PDOException;
use Psr\Log\InvalidArgumentException;
use mon\log\interfaces\RecordInterface;

/**
 * 数据库保存日志
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class DbRecord implements RecordInterface
{
    /**
     * Mysql链接实例
     *
     * @var PDO
     */
    protected $db;

    /**
     * 日志记录
     *
     * @var array
     */
    protected $logs = [];

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // 是否自动写入数据库
        'save'          => false,
        // 写入后，清除缓存日志
        'clear'         => true,
        // 服务器地址
        'host'          => '127.0.0.1',
        // 数据库名
        'database'      => '',
        // 表名
        'table'         => 'log',
        // 用户名
        'username'      => '',
        // 密码
        'password'      => '',
        // 端口
        'port'          => '3306',
        // 数据库编码默认采用utf8
        'charset'       => 'utf8mb4',
    ];

    /**
     * 构造方法
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 记录日志
     *
     * @param mixed $level      日志级别
     * @param string $message   日志信息
     * @param array $context    额外信息参数
     * @return boolean
     */
    public function record($level, string $message, array $context = []): bool
    {
        $this->logs[$level][] = [
            'content'       => $message,
            'uid'           => $context['uid'] ?? 0,
            'ip'            => $context['ip'] ?? '',
            'ext'           => $context['ext'] ?? '',
            'create_time'   => time()
        ];

        // 是否保存
        $save = $context['save'] ?? $this->config['save'];
        if ($save === true) {
            return $this->save($context);
        }

        return true;
    }

    /**
     * 保存日志
     *
     * @param array $context    配置参数
     * @return boolean
     */
    public function save(array $context = []): bool
    {
        if (!empty($this->logs)) {
            // 校验表名，防止SQL注入
            $table = $this->config['table'];
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
                throw new InvalidArgumentException("Invalid log table name: {$table}");
            }

            // 构建参数绑定的批量插入语句
            $placeholders = [];
            $params = [];
            $index = 0;
            foreach ($this->logs as $level => $logs) {
                foreach ($logs as $log) {
                    $placeholders[] = "(:level_{$index}, :content_{$index}, :uid_{$index}, :ip_{$index}, :ext_{$index}, :create_time_{$index})";
                    $params[":level_{$index}"] = (string) $level;
                    $params[":content_{$index}"] = (string) $log['content'];
                    $params[":uid_{$index}"] = (int) $log['uid'];
                    $params[":ip_{$index}"] = (string) $log['ip'];
                    // ext 可能为数组等非字符串类型，序列化后存储
                    $params[":ext_{$index}"] = is_array($log['ext']) ? json_encode($log['ext'], JSON_UNESCAPED_UNICODE) : (string) $log['ext'];
                    $params[":create_time_{$index}"] = (int) $log['create_time'];
                    $index++;
                }
            }

            // 写入数据库
            $sql = "INSERT INTO `{$table}` (level, content, uid, ip, ext, create_time) VALUES " . implode(', ', $placeholders);
            $save = $this->execute($sql, $params);
            $result = $save > 0;

            // 写入成功后，清除日志记录，失败时保留缓存以便重试
            $clear = $context['clear'] ?? $this->config['clear'];
            if ($result && $clear !== false) {
                $this->clearLog();
            }

            return $result;
        }

        return true;
    }

    /**
     * 获取日志缓存记录
     *
     * @return array
     */
    public function getLog(): array
    {
        return $this->logs;
    }

    /**
     * 清除日志缓存记录
     *
     * @return void
     */
    public function clearLog()
    {
        $this->logs = [];
    }

    /**
     * 设置配置信息
     *
     * @param array $config 配置信息
     * @return DbRecord
     */
    public function setConfig(array $config): DbRecord
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 获取配置信息
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }


    /**
     * 获取DB链接
     *
     * @return PDO
     */
    protected function getDB(): PDO
    {
        if (!$this->db) {
            // 生成mysql连接dsn
            $dsn = 'mysql:host=' . $this->config['host'];
            // 端口校验：仅接受合法的正整数端口
            $port = $this->config['port'];
            if (is_numeric($port) && (int) $port > 0) {
                $dsn .= ';port=' . (int) $port;
            }
            $dsn .= ';dbname=' . $this->config['database'];
            if (!empty($this->config['charset'])) {
                $dsn .= ';charset=' . $this->config['charset'];
            }
            // 数据库连接参数
            $params = [
                PDO::ATTR_CASE              => PDO::CASE_NATURAL,
                PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES  => false,
            ];
            // 链接
            $this->db = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $params
            );
        }

        return $this->db;
    }

    /**
     * 执行更新语句
     *
     * @param string $sql
     * @param array  $params 绑定参数
     * @throws PDOException
     * @return integer
     */
    protected function execute(string $sql, array $params = []): int
    {
        try {
            $query = $this->getDB()->prepare($sql);
            $query->execute($params);
            return $query->rowCount();
        } catch (PDOException $e) {
            // MySQL server has gone away(2006) / 连接中断(2013)时重置连接并重试一次，适配常驻内存场景
            $driverCode = $e->errorInfo[1] ?? 0;
            if (in_array($driverCode, [2006, 2013])) {
                $this->db = null;
                $query = $this->getDB()->prepare($sql);
                $query->execute($params);
                return $query->rowCount();
            }

            throw $e;
        }
    }
}
