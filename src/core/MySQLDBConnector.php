<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace bfmw\core;

use mysqli;
use mysqli_result;
use RuntimeException;

/**
 * Class MySQLDBConnector
 *
 * A concrete implementation of the Connected class for MySQL databases.
 * This class manages the connection using the mysqli extension and provides
 * helper methods for executing queries, fetching data, and handling transactions.
 *
 * @package bfmw\core
 */
class MySQLDBConnector implements DBConnector
{
    /**
     * The MySQLi connection instance.
     * @var mysqli
     */
    protected mysqli $conn;

    /**
     * Cache for query results, indexed by the raw SQL string.
     *
     * @var array<string, array>
     */
    private static array $queryCache = [];

    /**
     * MySQLDBConnector connector.
     *
     * Establishes a connection to the MySQL database using credentials
     * retrieved from environment variables (DB_SERVER, DB_USER, DB_PWD, DB_DATABASE).
     * Sets the character set to UTF-8.
     *
     * @throws RuntimeException If the connection to the database fails.
     */
    public function connect(): void
    {
        $this->conn = new mysqli(getenv('DB_SERVER'), getenv('DB_USER'), getenv('DB_PWD'), getenv('DB_DATABASE'));
        if ($this->conn->connect_errno) {
            throw new RuntimeException("La connexion à la base de données est impossible.");
        }
        $this->conn->set_charset('utf8');
    }

    /**
     * Terminates the MySQL connection if it has been established.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if (isset($this->conn)) {
            $this->conn->close();
        }
    }

    /**
     * Executes a query and fetches all results as an associative array.
     *
     * @param string $req The SQL query to execute.
     * @return array An array of associative arrays representing the result set.
     */
    public function getData(string $req) : array {
        return $this->executeQuery($req);
    }

    /**
     * Executes a query and returns results indexed by a specific column key.
     *
     * @param string $clef The column name to use as the array key.
     * @param string $req The SQL query to execute.
     * @return array An associative array where keys are values from the $clef column.
     */
    public function getDataWithKey(string $clef, string $req) : array {
        $res = $this->conn->query($req);
        $retour = array();
        if ($res instanceof mysqli_result) {
            while ($une_valeur = $res->fetch_assoc()) {
                $retour[$une_valeur[$clef]] = $une_valeur;
            }
        }
        return $retour;
    }

    /**
     * Executes a query and returns the raw mysqli_result object.
     *
     * @param string $req The SQL query to execute.
     * @return mysqli_result|null The result object or null on failure.
     */
    public function doRequest(string $req) : ?mysqli_result {
        $retour = $this->conn->query($req);
        return $retour instanceof mysqli_result ? $retour : null;
    }

    /**
     * Executes a query and returns the success status.
     *
     * @param string $req The SQL query to execute.
     * @return bool True on success, false on failure.
     */
    public function execRequest(string $req) : bool {
        return  $this->executeQuery($req) !== false;
    }

    /**
     * Executes multiple SQL queries separated by semicolons.
     *
     * @param string $req The string containing multiple SQL queries.
     * @return bool True if all queries were processed successfully.
     */
    public function execMultiRequest(string $req) : bool {
        $retour = true;
        $retour &= $this->conn->multi_query($req);
        while ($this->conn->more_results()) {
            $retour &= $this->conn->next_result();
        }
        return $retour;
    }

    /**
     * Executes a query and returns the number of affected rows.
     *
     * Useful for UPDATE or DELETE operations.
     *
     * @param string $req The SQL query to execute.
     * @return int The number of rows affected by the query.
     */
    public function execRequestReturnAffectedLines(string $req) : int {
        $this->conn->query($req);
        return $this->conn->affected_rows;
    }

    /**
     * Executes an INSERT query and returns the auto-generated ID.
     *
     * @param string $req The SQL INSERT query.
     * @return int The ID of the last inserted row.
     */
    public function execRequestReturnLastId(string $req) : int {
        $this->conn->query($req);
        return $this->conn->insert_id;
    }

    /**
     * Starts a new database transaction.
     *
     * @return void
     */
    public function startTransaction() : void
    {
        $this->conn->query("BEGIN");
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     */
    public function commit() : void
    {
        $this->conn->query("COMMIT");
    }

    /**
     * Rolls back the current transaction.
     *
     * @return void
     */
    public function rollback() : void
    {
        $this->conn->query("ROLLBACK");
    }

    /**
     * Generates and executes an INSERT SQL query.
     *
     * Constructs an INSERT statement based on the provided data array.
     * Only processes keys starting with 'bfmw_orig_*'.
     * Uses 'bfmw_num_' variant if valid, otherwise 'bfmw_orig_'.
     * Relies on a global $conn variable (mysqli object).
     *
     * @param string $table_name The target database table.
     * @param array $data The data array containing 'bfmw_orig_*' keys.
     * @param int $fields_to_use Number of fields expected to be processed.
     * @param bool $show_query Whether to echo/log the generated query (default: false).
     * @return int The last inserted ID on success, or -1 on failure.
     */
    public function createData(string $table_name,array $data,int $fields_to_use,bool $show_query = false) : int
    {
        $req = "INSERT INTO $table_name(";
        $clefs = "";
        $valeurs = "";
        $i = 0;

        foreach ($data as $key => $value) {
            $key = str_replace("bfmw_orig_","",$key);
            if ($key[0] == "*") {
                $fields_to_use--;
                if ($i++ != 0) {
                    $clefs .= ",";
                    $valeurs .= ",";
                }
                $clefs .= substr($key, 1);
                if ($data["bfmw_num_".$key] !== null && $data["bfmw_num_".$key] !== "") {
                    $valeurs .= $data["bfmw_num_".$key];
                } else {
                    if ($value == "NULL") {
                        $valeurs .= "NULL";
                    } else {
                        $valeurs .= "'$value'";
                    }
                }

            }
        }
        $req .= "$clefs) VALUES ($valeurs)";

        if ($show_query) {
            echo $req;
        }

        if ($fields_to_use === 0) {

            try {
                if ($this->conn->query($req)) {
                    return $this->conn->insert_id;
                }
            } catch (RuntimeException $e) {
                return -1;
            }
        }
        return -1;
    }

    /**
     * Execute a query with per-instance caching for read-only statements.
     *
     * @param string $req
     * @return array<row<column_name,value>>
     */
    private function executeQuery(string $req): array
    {
        if (!isset(self::$queryCache[$req])) {
            $result = $this->conn->query($req);
            self::$queryCache[$req] = $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        return self::$queryCache[$req];
    }
}