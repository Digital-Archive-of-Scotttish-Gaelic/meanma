<?php

namespace models;

class DynamicModel implements \JsonSerializable
{
    protected \PDO $pdo;
    protected string $table;
    protected string $primaryKey;
    protected array $columns = [];     // list of column names
    protected array $data = [];        // current values
    protected array $original = [];    // loaded values
    protected array $dirty = [];       // field => true

    public function __construct(\PDO $pdo, string $table, string $primaryKey = 'id', array $data = [])
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->reloadColumns();        // discover schema now (fast: DESCRIBE)
        if ($data) $this->fill($data, false);
    }

    /* ---------- Schema ---------- */

    public function reloadColumns(): void
    {
        $stmt = $this->pdo->query('DESCRIBE `' . str_replace('`','``',$this->table) . '`');
        $this->columns = array_map(fn($r) => $r['Field'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
        // Ensure we don’t carry stale values for dropped columns
        $this->data = array_intersect_key($this->data, array_flip($this->columns));
        $this->original = array_intersect_key($this->original, array_flip($this->columns));
        $this->dirty = array_intersect_key($this->dirty, array_flip($this->columns));
    }

    public function getColumns(): array { return $this->columns; }

    /* ---------- Data access ---------- */

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {
        if (!in_array($name, $this->columns, true)) {
            throw new InvalidArgumentException("Column '$name' does not exist on {$this->table}");
        }
        $orig = $this->data[$name] ?? null;
        $this->data[$name] = $value;
        // mark dirty only if changed (loose compare to catch '5' vs 5 differences if needed)
        if ($orig !== $value) $this->dirty[$name] = true;
    }

    public function fill(array $values, bool $markDirty = true): self
    {
        foreach ($values as $k => $v) {
            if (in_array($k, $this->columns, true)) {
                $this->data[$k] = $v;
                if ($markDirty) $this->dirty[$k] = true;
            }
        }
        if (!$markDirty) {
            $this->original = $this->data;
            $this->dirty = [];
        }
        return $this;
    }

    public function toArray(): array { return $this->data; }
    public function jsonSerialize(): mixed { return $this->toArray(); }
    public function isDirty(): bool { return !empty($this->dirty); }

    /* ---------- CRUD ---------- */

    public static function find(\PDO $pdo, string $table, $id, string $primaryKey = 'id'): ?self
    {
        $model = new self($pdo, $table, $primaryKey);
        $sql = 'SELECT * FROM `' . str_replace('`','``',$table) . '` WHERE `'.$primaryKey.'` = ? LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (new self($pdo, $table, $primaryKey, $row)) : null;
    }

    /** Simple AND-equality where; returns array<DynamicModel> */
    public static function where(\PDO $pdo, string $table, array $conds = [], string $primaryKey = 'id'): array
    {
        $model = new self($pdo, $table, $primaryKey);
        $cols = $model->getColumns();
        $clauses = [];
        $params = [];
        foreach ($conds as $k => $v) {
            if (!in_array($k, $cols, true)) continue; // ignore unknown fields
            $clauses[] = '`'.$k.'` = ?';
            $params[] = $v;
        }
        $sql = 'SELECT * FROM `'.str_replace('`','``',$table).'`'.
            ( $clauses ? ' WHERE '.implode(' AND ', $clauses) : '' );
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($pdo, $table, $primaryKey, $r), $rows);
    }

    public function save(): void
    {
        // If schema might have changed at runtime, uncomment next line:
        // $this->reloadColumns();

        $hasId = isset($this->data[$this->primaryKey]) && $this->data[$this->primaryKey] !== null;

        if ($hasId) {
            // UPDATE only dirty columns (except PK)
            $fields = array_keys(array_diff_key($this->dirty, [$this->primaryKey => true]));
            if (empty($fields)) return; // nothing to do
            $set = implode(', ', array_map(fn($c) => '`'.$c.'` = ?', $fields));
            $sql = 'UPDATE `'.str_replace('`','``',$this->table).'` SET '.$set.' WHERE `'.$this->primaryKey.'` = ?';
            $params = array_map(fn($c) => $this->data[$c], $fields);
            $params[] = $this->data[$this->primaryKey];
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // INSERT only known, non-null columns (except auto PK if null)
            $insertCols = array_values(array_filter(
                $this->columns,
                fn($c) => array_key_exists($c, $this->data) && !($c === $this->primaryKey && $this->data[$c] === null)
            ));
            if (empty($insertCols)) {
                throw new RuntimeException('Nothing to insert.');
            }
            $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
            $colList = implode('`, `', $insertCols);
            $sql = 'INSERT INTO `'.str_replace('`','``',$this->table).'` (`'.$colList.'`) VALUES ('.$placeholders.')';
            $params = array_map(fn($c) => $this->data[$c], $insertCols);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // set PK if auto-increment
            if (!isset($this->data[$this->primaryKey])) {
                $this->data[$this->primaryKey] = $this->pdo->lastInsertId();
            }
        }

        // sync state
        $this->original = $this->data;
        $this->dirty = [];
    }

    public function delete(): void
    {
        if (!isset($this->data[$this->primaryKey])) return;
        $sql = 'DELETE FROM `'.str_replace('`','``',$this->table).'` WHERE `'.$this->primaryKey.'` = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->data[$this->primaryKey]]);
        $this->data = [];
        $this->original = [];
        $this->dirty = [];
    }
}