<?php

namespace engine;

use mysqli;
use mysqli_result;

class MySql
{

    const RESULT_ASSOC = MYSQLI_ASSOC;
    const RESULT_NUM = MYSQLI_NUM;
    protected null|false|mysqli $conn;
    protected array $stats;
    protected mixed $errorMode;
    protected mixed $exceptionName;
    protected array $defaults = array(
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'test',
        'port' => NULL,
        'socket' => NULL,
        'pconnect' => FALSE,
        'charset' => 'utf8',
        'errorMode' => 'exception', //or 'error'
        'exception' => 'Exception', //Exception class name
    );

    function __construct($opt = array())
    {
        $opt = array_merge($this->defaults, $opt);

        $this->errorMode = $opt['errorMode'];
        $this->exceptionName = $opt['exception'];

        if (isset($opt['mysqli'])) {
            if ($opt['mysqli'] instanceof mysqli) {
                $this->conn = $opt['mysqli'];
                return;

            } else {

                $this->error("mysqli option must be valid instance of mysqli class");
            }
        }

        if ($opt['pconnect']) {
            $opt['host'] = "p:" . $opt['host'];
        }

        @$this->conn = mysqli_connect($opt['host'], $opt['user'], $opt['pass'], $opt['db'], $opt['port'], $opt['socket']);
        if (!$this->conn) {
            $this->error(mysqli_connect_errno() . " " . mysqli_connect_error());
        }

        mysqli_set_charset($this->conn, $opt['charset']) or $this->error(mysqli_error($this->conn));
        unset($opt); // I am paranoid
    }

    protected function error($err)
    {
        $err = __CLASS__ . ": " . $err;

        if ($this->errorMode == 'error') {
            $err .= ". Error initiated in " . $this->caller() . ", thrown";
            trigger_error($err, E_USER_ERROR);
        } else {
            throw new $this->exceptionName($err);
        }
    }

    protected function caller(): string
    {
        $trace = debug_backtrace();
        $caller = '';
        foreach ($trace as $t) {
            if (isset($t['class']) && $t['class'] == __CLASS__) {
                $caller = $t['file'] . " on line " . $t['line'];
            } else {
                break;
            }
        }
        return $caller;
    }

    /**
     * Conventional function to run a query with placeholders. A mysqli_query wrapper with placeholders support
     *
     * Examples:
     * $db->query("DELETE FROM table WHERE id=?i", $id);
     *
     * @return bool|mysqli_result whatever mysqli_query returns
     */
    public function query(): bool|mysqli_result
    {
        return $this->rawQuery($this->prepareQuery(func_get_args()));
    }

    /**
     * protected function which actually runs a query against Mysql server.
     * also logs some stats like profiling info and error message
     *
     * @param string $query - a regular SQL query
     * @return bool|mysqli_result result resource or FALSE on error
     */
    public function rawQuery(string $query): bool|mysqli_result
    {
        $start = microtime(TRUE);
        $res = mysqli_query($this->conn, $query);
        $timer = microtime(TRUE) - $start;

        $this->stats[] = array(
            'query' => $query,
            'start' => $start,
            'timer' => $timer,
        );
        if (!$res) {
            $error = mysqli_error($this->conn);

            end($this->stats);
            $key = key($this->stats);
            $this->stats[$key]['error'] = $error;
            $this->cutStats();

            $this->error("$error. Full query: [$query]");
        }
        $this->cutStats();
        return $res;
    }

    /**
     * On a long run we can eat up too much memory with mere statsistics
     * Let's keep it at reasonable size, leaving only last 100 entries.
     */
    protected function cutStats()
    {
        if (count($this->stats) > 100) {
            reset($this->stats);
            $first = key($this->stats);
            unset($this->stats[$first]);
        }
    }

    protected function prepareQuery($args): string
    {
        $query = '';
        $raw = array_shift($args);
        $array = preg_split('~(\?[nsiuap])~u', $raw, null, PREG_SPLIT_DELIM_CAPTURE);
        $anum = count($args);
        $pnum = floor(count($array) / 2);
        if ($pnum != $anum) {
            $this->error("Number of args ($anum) doesn't match number of placeholders ($pnum) in [$raw]");
        }

        foreach ($array as $i => $part) {
            if (($i % 2) == 0) {
                $query .= $part;
                continue;
            }

            $value = array_shift($args);
            $part = match ($part) {
                '?n' => $this->escapeIdent($value),
                '?s' => $this->escapeString($value),
                '?i' => $this->escapeInt($value),
                '?a' => $this->createIN($value),
                '?u' => $this->createSET($value),
                '?p' => $value,
            };
            $query .= $part;
        }
        return $query;
    }

    protected function escapeIdent($value): string|null
    {
        if ($value) {
            return "`" . str_replace("`", "``", $value) . "`";
        } else {
            $this->error("Empty value for identifier (?n) placeholder");
        }

        return null;
    }

    protected function escapeString($value): string
    {
        if ($value === NULL) {
            return 'NULL';
        }
        return "'" . mysqli_real_escape_string($this->conn, mb_convert_encoding($value, 'cp1251', 'utf-8')) . "'";
    }

    protected function escapeInt($value): bool|int|string
    {
        if ($value === NULL) {
            return 'NULL';
        }
        if (!is_numeric($value)) {
            $this->error("Integer (?i) placeholder expects numeric value, " . gettype($value) . " given");
            return FALSE;
        }
        if (is_float($value)) {
            $value = number_format($value, 0, '.', ''); // may lose precision on big numbers
        }
        return $value;
    }

    protected function createIN($data): ?string
    {
        if (!is_array($data)) {
            $this->error("Value for IN (?a) placeholder should be array");
            return null;
        }
        if (!$data) {
            return 'NULL';
        }
        $query = $comma = '';
        foreach ($data as $value) {
            $query .= $comma . $this->escapeString($value);
            $comma = ",";
        }
        return $query;
    }

    protected function createSET($data): ?string
    {
        if (!is_array($data)) {
            $this->error("SET (?u) placeholder expects array, " . gettype($data) . " given");
            return null;
        }
        if (!$data) {
            $this->error("Empty array for SET (?u) placeholder");
            return null;
        }
        $query = $comma = '';
        foreach ($data as $key => $value) {
            $query .= $comma . $this->escapeIdent($key) . '=' . $this->escapeString($value);
            $comma = ",";
        }
        return $query;
    }

    /**
     * Conventional function to get number of affected rows.
     *
     * @return int whatever mysqli_affected_rows returns
     */
    public function affectedRows(): int
    {
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Conventional function to get last insert id.
     *
     * @return int whatever mysqli_insert_id returns
     */
    public function insertId(): int
    {
        return mysqli_insert_id($this->conn);
    }

    /**
     * @param $result
     * @return int
     */
    public function numRows($result): int
    {
        return mysqli_num_rows($result);
    }

    /**
     * Helper function to get scalar value right out of query and optional arguments
     *
     * Examples:
     * $name = $db->getOne("SELECT name FROM table WHERE id=1");
     * $name = $db->getOne("SELECT name FROM table WHERE id=?i", $id);
     *
     * @return string either first column of the first row of resultset or FALSE if none found
     */
    public function getOne(): string
    {
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->rawQuery($query)) {
            $row = $this->fetch($res);
            if (is_array($row)) {
                return reset($row);
            }
            $this->free($res);
        }
        return FALSE;
    }

    /**
     * Conventional function to fetch single row.
     *
     * @param resource $result - myqli result
     * @param int $mode - optional fetch mode, RESULT_ASSOC|RESULT_NUM, default RESULT_ASSOC
     * @return array whatever mysqli_fetch_array returns
     */
    public function fetch($result, int $mode = self::RESULT_ASSOC)
    {
        return mysqli_fetch_array($result, $mode);
    }

    /**
     * Conventional function to free the resultset.
     */
    public function free($result)
    {
        mysqli_free_result($result);
    }

    /**
     * Helper function to get single row right out of query and optional arguments
     *
     * Examples:
     * $data = $db->getRow("SELECT * FROM table WHERE id=1");
     * $data = $db->getRow("SELECT * FROM table WHERE id=?i", $id);
     *
     * @return array|bool either associative array contains first row of resultset or FALSE if none found
     */
    public function getRow(): array|bool
    {
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->rawQuery($query)) {
            $ret = $this->fetch($res);
            $this->free($res);
            return $ret;
        }
        return FALSE;
    }

    /**
     * Helper function to get single column right out of query and optional arguments
     *
     * Examples:
     * $ids = $db->getCol("SELECT id FROM table WHERE cat=1");
     * $ids = $db->getCol("SELECT id FROM tags WHERE tagname = ?s", $tag);
     *
     * @return array enumerated array of first fields of all rows of resultset or empty array if none found
     */
    public function getCol(): array
    {
        $ret = array();
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->rawQuery($query)) {
            while ($row = $this->fetch($res)) {
                $ret[] = reset($row);
            }
            $this->free($res);
        }
        return $ret;
    }

    /**
     * Helper function to get all the rows of resultset right out of query and optional arguments
     *
     * Examples:
     * $data = $db->getAll("SELECT * FROM table");
     * $data = $db->getAll("SELECT * FROM table LIMIT ?i,?i", $start, $rows);
     *
     * @return array enumerated 2d array contains the resultset. Empty if no rows found.
     */
    public function getAll(): array
    {
        $ret = array();
        $query = $this->prepareQuery(func_get_args());
        if ($res = $this->rawQuery($query)) {
            while ($row = $this->fetch($res)) {
                $ret[] = $row;
            }
            $this->free($res);
        }
        return $ret;
    }

    /**
     * Helper function to get all the rows of resultset into indexed array right out of query and optional arguments
     *
     * Examples:
     * $data = $db->getInd("id", "SELECT * FROM table");
     * $data = $db->getInd("id", "SELECT * FROM table LIMIT ?i,?i", $start, $rows);
     *
     * @return array - associative 2d array contains the resultset. Empty if no rows found.
     */
    public function getInd(): array
    {
        $args = func_get_args();
        $index = array_shift($args);
        $query = $this->prepareQuery($args);

        $ret = array();
        if ($res = $this->rawQuery($query)) {
            while ($row = $this->fetch($res)) {
                $ret[$row[$index]] = $row;
            }
            $this->free($res);
        }
        return $ret;
    }

    /**
     * Helper function to get a dictionary-style array right out of query and optional arguments
     *
     * Examples:
     * $data = $db->getIndCol("name", "SELECT name, id FROM cities");
     *
     * @return array - associative array contains key=value pairs out of resultset. Empty if no rows found.
     */
    public function getIndCol(): array
    {
        $args = func_get_args();
        $index = array_shift($args);
        $query = $this->prepareQuery($args);

        $ret = array();
        if ($res = $this->rawQuery($query)) {
            while ($row = $this->fetch($res)) {
                $key = $row[$index];
                unset($row[$index]);
                $ret[$key] = reset($row);
            }
            $this->free($res);
        }
        return $ret;
    }

    /**
     * Function to parse placeholders either in the full query or a query part
     * unlike native prepared statements, allows ANY query part to be parsed
     *
     * useful for debug
     * and EXTREMELY useful for conditional query building
     * like adding various query parts using loops, conditions, etc.
     * already parsed parts have to be added via ?p placeholder
     *
     * Examples:
     * $query = $db->parse("SELECT * FROM table WHERE foo=?s AND bar=?s", $foo, $bar);
     * echo $query;
     *
     * if ($foo) {
     *     $qpart = $db->parse(" AND foo=?s", $foo);
     * }
     * $data = $db->getAll("SELECT * FROM table WHERE bar=?s ?p", $bar, $qpart);
     *
     * @return string - initial expression with placeholders substituted with data.
     */
    public function parse(): string
    {
        return $this->prepareQuery(func_get_args());
    }

    /**
     * function to implement whitelisting feature
     * sometimes we can't allow a non-validated user-supplied data to the query even through placeholder
     * especially if it comes down to SQL OPERATORS
     *
     * Example:
     *
     * $order = $db->whiteList($_GET['order'], array('name','price'));
     * $dir   = $db->whiteList($_GET['dir'],   array('ASC','DESC'));
     * if (!$order || !dir) {
     *     throw new http404(); //non-expected values should cause 404 or similar response
     * }
     * $sql  = "SELECT * FROM table ORDER BY ?p ?p LIMIT ?i,?i"
     * $data = $db->getArr($sql, $order, $dir, $start, $per_page);
     *
     * @param $input
     * @param array $allowed - an array with allowed variants
     * @param bool|string $default - optional variable to set if no match found. Default to false.
     * @return string - either sanitized value or FALSE
     */
    public function whiteList($input, array $allowed, bool|string $default = FALSE): string
    {
        $found = array_search($input, $allowed);
        return ($found === FALSE) ? $default : $allowed[$found];
    }

    /**
     * function to filter out arrays, for the whitelisting purposes
     * useful to pass entire superglobal to the INSERT or UPDATE query
     * OUGHT to be used for this purpose,
     * as there could be fields to which user should have no access to.
     *
     * Example:
     * $allowed = array('title','url','body','rating','term','type');
     * $data    = $db->filterArray($_POST,$allowed);
     * $sql     = "INSERT INTO ?n SET ?u";
     * $db->query($sql,$table,$data);
     *
     * @param array $input - source array
     * @param array $allowed - an array with allowed field names
     * @return array filtered out source array
     */
    public function filterArray(array $input, array $allowed): array
    {
        foreach (array_keys($input) as $key) {
            if (!in_array($key, $allowed)) {
                unset($input[$key]);
            }
        }
        return $input;
    }

    /**
     * Function to get last executed query.
     *
     * @return string|NULL either last executed query or NULL if were none
     */
    public function lastQuery(): ?string
    {
        $last = end($this->stats);
        return $last['query'];
    }

    /**
     * Function to get all query statistics.
     *
     * @return array contains all executed queries with timings and errors
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
