<?php
/**
 * Database.php
 *
 * Provides a static method to establish a MySQL database connection using mysqli.
 *
 * PHP version 7+
 *
 * @package    AcademyDB
 * @author     Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license    MIT License
 */

/**
 * Class Database
 *
 * Utility class for connecting to the MySQL database.
 */
class Database {
    /**
     * Establishes a new mysqli connection to the academy_db database.
     *
     * @return \mysqli The mysqli connection object.
     * @throws \Exception If the connection fails.
     */
    public static function connect() {
        $con = new mysqli('localhost', 'silversoth', 'PucelaSpain52686', 'businessfirst');
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        }
        return $con;
    }
}