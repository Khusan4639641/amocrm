<?php

class BaseModel
{
    protected  $pdo;
    protected  $table;
    protected  $primaryKey = 'id';
    protected  $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    
        $configFile = __DIR__ . '/../amo_config.json';
    
        if (!file_exists($configFile)) {
            throw new \Exception('Файл конфигурации не найден: ' . $configFile);
        }
    
        $config = json_decode(file_get_contents($configFile), true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка разбора JSON: ' . json_last_error_msg());
        }
    
        try {
            $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['db_root'], $config['db_pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Ошибка подключения к базе данных: ' . $e->getMessage());
        }
    }


    public function set($key,  $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function save(): bool
    {
        if (isset($this->attributes[$this->primaryKey])) {
            // UPDATE
            $columns = [];
            foreach ($this->attributes as $key => $value) {
                if ($key !== $this->primaryKey) {
                    $columns[] = "`$key` = :$key";
                }
            }

            $sql = "UPDATE `{$this->table}` SET " . implode(', ', $columns) .
                   " WHERE `{$this->primaryKey}` = :primaryKey";

            $stmt = $this->pdo->prepare($sql);
            foreach ($this->attributes as $key => $value) {
                if ($key !== $this->primaryKey) {
                    $stmt->bindValue(":$key", $value);
                }
            }
            $stmt->bindValue(':primaryKey', $this->attributes[$this->primaryKey]);
        } else {
            // INSERT
            $keys = array_keys($this->attributes);
            $columns = '`' . implode('`, `', $keys) . '`';
            $placeholders = ':' . implode(', :', $keys);

            $sql = "INSERT INTO `{$this->table}` ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            foreach ($this->attributes as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
        }

        return $stmt->execute();
    }

    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $this->attributes[$this->primaryKey]);

        return $stmt->execute();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new static($data) : null;
    }

    public function where(array $conditions): array
    {
        $where = [];
        foreach ($conditions as $key => $value) {
            $where[] = "`$key` = :$key";
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new static($row);
        }

        return $results;
    }
}
