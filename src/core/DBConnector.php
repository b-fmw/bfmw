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

/**
 * Class DBConnector
 *
 * Interface defining the contract for database connection handlers.
 * It declares standard methods for data retrieval, query execution, and transaction management
 * that any concrete database adapter (e.g., for MySQL, PostgreSQL) must implement.
 *
 * @package bfmw\core
 */
interface DBConnector
{
    /**
     * Establishes a database connection.
     * @return void
     */
    function connect() : void;

    /**
     * Closes the active database connection.
     *
     * @return void
     */
    function disconnect() : void;
    /**
     * Executes a query and fetches all results as an array.
     *
     * @param string $req The SQL query to execute.
     * @return array An array representing the result set.
     */
    function getData(string $req) : array;
    /**
     * Executes a query and returns results indexed by a specific key.
     *
     * @param string $clef The column name to use as the array key.
     * @param string $req The SQL query to execute.
     * @return array An associative array indexed by the specified key.
     */
    function getDataWithKey(string $clef, string $req) : array;
    /**
     * Executes a query and returns the raw result object.
     *
     * @param string $req The SQL query to execute.
     * @return mixed The database-specific result object or null/false on failure.
     */
    function doRequest(string $req) ;
    /**
     * Executes a query and returns the success status.
     *
     * @param string $req The SQL query to execute.
     * @return bool True on success, false on failure.
     */
    function execRequest(string $req) : bool ;
    /**
     * Executes multiple SQL queries.
     *
     * @param string $req The string containing multiple SQL queries.
     * @return bool True if all queries were processed successfully.
     */
    function execMultiRequest(string $req) : bool;
    /**
     * Executes a query and returns the number of affected rows.
     *
     * @param string $req The SQL query to execute.
     * @return int The number of rows affected.
     */
    function execRequestReturnAffectedLines(string $req) : int ;
    /**
     * Executes an INSERT query and returns the generated ID.
     *
     * @param string $req The SQL INSERT query.
     * @return int The ID of the last inserted row.
     */
    function execRequestReturnLastId(string $req) : int;
    /**
     * Starts a database transaction.
     *
     * @return void
     */
    function startTransaction() : void;
    /**
     * Commits the current transaction.
     *
     * @return void
     */
    function commit() : void;
    /**
     * Rolls back the current transaction.
     *
     * @return void
     */
    function rollback() : void;
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
    function createData(string $table_name,array $data,int $fields_to_use,bool $show_query = false) : int;
}