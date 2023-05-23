<?php

declare(strict_types=1);

namespace mon\log\record;

use PDO;
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
     * @param array $context    信息参数
     * @return bool
     */
    public function record($level, string $message, array $context = [])
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
            $values = [];
            foreach ($this->logs as $level => $logs) {
                $lv = $this->getDB()->quote((string) $level);
                foreach ($logs as $log) {
                    $item = array_map(function ($v) {
                        return $this->getDB()->quote((string) $v);
                    }, $log);
                    $item['level'] = $lv;
                    $values[] = "({$item['level']}, {$item['content']}, {$item['uid']}, {$item['ip']}, {$item['ext']}, {$item['create_time']})";
                }
            }

            if (!empty($values)) {
                $sql = "INSERT INTO {$this->config['table']} (level, content, uid, ip, ext, create_time) VALUES " . implode(', ', $values);
                return $this->execute($sql);
            }

            // 默认保存后，清除日志记录
            $clear = $context['clear'] ?? $this->config['clear'];
            if ($clear !== false) {
                $this->clearLog();
            }
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
     * @throws PDOException
     * @return PDO
     */
    protected function getDB(): PDO
    {
        if (!$this->db) {
            // 生成mysql连接dsn
            $is_port = (isset($this->config['port']) && is_int($this->config['port'] * 1));
            $dsn = 'mysql:host=' . $this->config['host'] . ($is_port ? ';port=' . $this->config['port'] : '') . ';dbname=' . $this->config['database'];
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
     * @return integer
     */
    protected function execute(string $sql): int
    {
        $query = $this->getDB()->prepare($sql);
        $query->execute();
        return $query->rowCount();
    }
}
