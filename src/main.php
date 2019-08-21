<?php

require_once(__DIR__ . '/vendor/autoload.php');


// specify number of products to test against (num rows in tables)
define("NUM_PRODUCTS", 500000);

# specify number of queries to run for benchmarking
define("NUM_QUERIES", 1000);

// specify database connection details
define("MYSQL_HOST", "localhost");
define("MYSQL_DB_NAME", "benchmarking");
define("MYSQL_USER", "root");
define("MYSQL_PASSWORD", "setYourPasswordHere");
define("MYSQL_PORT", 3306);



function getDb() : mysqli
{
    $db = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB_NAME, MYSQL_PORT);

    if ($db === false)
    {
        die("Failed to connect to database, check your settings.");
    }

    return $db;
}

function query(string $query)
{
    $db = getDb();
    $result = $db->query($query);

    if ($result === false)
    {
        print "Database query failed." . PHP_EOL;
        print $db->error . PHP_EOL;
        print "query: {$query}" . PHP_EOL;
        die();
    }

    return $result;
}



function createDatabaseTables()
{
    $queries = array(
        "DROP TABLE IF EXISTS `integer_table`",
        "DROP TABLE IF EXISTS `varchar_table`",
    );


    $queries[] =
        "CREATE TABLE `integer_table` (
        `barcode` BIGINT unsigned NOT NULL,
        `field1` varchar(255) NOT NULL,
        `field2` varchar(255) NOT NULL,
        `field3` varchar(255) NOT NULL,
        `field4` varchar(255) NOT NULL,
        `field5` varchar(255) NOT NULL,
        `field6` varchar(255) NOT NULL,
        `field7` varchar(255) NOT NULL,
        `field8` varchar(255) NOT NULL,
        PRIMARY KEY (`barcode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $queries[] = "CREATE TABLE `varchar_table` (
        `barcode` varchar(44) NOT NULL,
        `field1` varchar(255) NOT NULL,
        `field2` varchar(255) NOT NULL,
        `field3` varchar(255) NOT NULL,
        `field4` varchar(255) NOT NULL,
        `field5` varchar(255) NOT NULL,
        `field6` varchar(255) NOT NULL,
        `field7` varchar(255) NOT NULL,
        `field8` varchar(255) NOT NULL,
        PRIMARY KEY (`barcode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    foreach ($queries as $query)
    {
        query($query);
    }
}


function insertFakeProducts()
{
    print "Inserting fake product data..." . PHP_EOL;

    $integerTableData = array();
    $varcharTableData = array();

    for ($i=0; $i<NUM_PRODUCTS; $i++)
    {
        $barcodeInt = $i;
        $barcodeVarchar = decbin($i);
        $randString = md5($i);

        $strings = array(
            'field1' => $randString,
            'field2' => $randString,
            'field3' => $randString,
            'field4' => $randString,
            'field5' => $randString,
            'field6' => $randString,
            'field7' => $randString,
            'field8' => $randString
        );

        $integerTableData[] = array_merge($strings, ['barcode' => $barcodeInt]);
        $varcharTableData[] = array_merge($strings, ['barcode' => $barcodeVarchar]);

        if ($i % 5000 === 0)
        {
            \iRAP\Profiling\FunctionAnalyzer::start('insert_integer');
            getDb()->query(iRAP\CoreLibs\MysqliLib::generateBatchInsertQuery($integerTableData, "integer_table", getDb()));
            \iRAP\Profiling\FunctionAnalyzer::stop('insert_integer');

            \iRAP\Profiling\FunctionAnalyzer::start('insert_varchar');
            getDb()->query(iRAP\CoreLibs\MysqliLib::generateBatchInsertQuery($varcharTableData, "varchar_table", getDb()));
            \iRAP\Profiling\FunctionAnalyzer::stop('insert_varchar');
            $integerTableData = [];
            $varcharTableData = [];
        }
    }

    \iRAP\Profiling\FunctionAnalyzer::stop('insert_integer');
    getDb()->query(iRAP\CoreLibs\MysqliLib::generateBatchInsertQuery($integerTableData, "integer_table", getDb()));
    \iRAP\Profiling\FunctionAnalyzer::stop('insert_integer');

    \iRAP\Profiling\FunctionAnalyzer::start('insert_varchar');
    getDb()->query(iRAP\CoreLibs\MysqliLib::generateBatchInsertQuery($varcharTableData, "varchar_table", getDb()));
    \iRAP\Profiling\FunctionAnalyzer::stop('insert_varchar');
}


function benchmarkIntegerTable(array $barcodes)
{
    \iRAP\Profiling\FunctionAnalyzer::start('read_integer');

    foreach ($barcodes as $barcode)
    {
        $intVal = bindec($barcode);
        query("SELECT * FROM `integer_table` WHERE `barcode`={$intVal}");
    }

    \iRAP\Profiling\FunctionAnalyzer::stop('read_integer');
}


function benchmarkVarcharTable(array $barcodes)
{
    \iRAP\Profiling\FunctionAnalyzer::start('read_varchar');

    foreach ($barcodes as $barcode)
    {
        query("SELECT * FROM `varchar_table` WHERE `barcode`='{$barcode}'");
    }

    \iRAP\Profiling\FunctionAnalyzer::stop('read_varchar');
}


function generateRandomBarcodesToLookUp()
{
    $barcodes = array();

    while (count($barcodes) < NUM_QUERIES)
    {
        $barcodes[decbin(random_int(0, 9999999999999))] = 1;
    }

    return array_keys($barcodes);
}


function main()
{
    print "Creating fresh database of fake data." . PHP_EOL;
    createDatabaseTables();
    insertFakeProducts();
    print "Database initialized, performing test." . PHP_EOL;
    $barcodes = generateRandomBarcodesToLookUp();
    benchmarkIntegerTable($barcodes);
    benchmarkVarcharTable($barcodes);
    print "Benchark results on " . NUM_PRODUCTS . " products." . PHP_EOL;
    print \iRAP\Profiling\FunctionAnalyzer::getResults() . PHP_EOL;
}

main();

