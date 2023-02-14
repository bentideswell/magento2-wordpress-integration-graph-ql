<?php
/**
 *
 */
$args = [];
foreach ($argv as $arg) {
    if (preg_match('/^\-\-(.*)=(.*)$/', $arg, $match)) {
        $args[$match[1]] = $match[2];
    } elseif (strpos($arg, '--') === 0) {
           $args[substr($arg, 2)] = true;
    }
}

define('GRAPHQL_ENDPOINT', $args['endpoint'] ?? 'https://m2wp.local.fishpig.com/graphql');

$queryDir = __DIR__ . '/testdata/queries';

if (!is_dir($queryDir)) {
    echo 'Could not find query directory.' . PHP_EOL;
    exit(1);
}

if (isset($args['help'])) {
    echo 'Options:' . PHP_EOL;
    echo '  --endpoint=https://www.yourendpoint.com/graphql' . PHP_EOL;
    echo '  --all to run all queries' . PHP_EOL;
    echo '  --{query-name} to run specific query and view output' . PHP_EOL;
    echo '  --post-by-id would run the specific query' . PHP_EOL;
    echo PHP_EOL;
    echo 'Queries can be found in ' . $queryDir . PHP_EOL . PHP_EOL;
    exit;
}

$runAll = isset($args['all']);
// If set (--debug) output will be shown
$debug = isset($args['debug']);
$hr = str_repeat('#', 64) . PHP_EOL;

foreach (array_slice(scandir($queryDir), 2) as $queryFile) {
    $absoluteQueryFile = $queryDir . '/' . $queryFile;
    $queryId = basename($queryFile, '.graphql');
    $runSpecific = isset($args[$queryId]);

    if ($runAll || $runSpecific) {
        $query = trim(file_get_contents($absoluteQueryFile));

        if ($debug) {
            echo $hr . '# ' . $queryId . PHP_EOL . $hr . PHP_EOL;
            echo $query . PHP_EOL . PHP_EOL;
            print_r(doQuery($query));
            echo PHP_EOL . PHP_EOL;
        } else {
            doQuery($query);
        }
    }
}

/**
 * Run a query using CURL
 */
function doQuery(string $query)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GRAPHQL_ENDPOINT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $result = curl_exec($ch);

    if ($errorNo = curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \RuntimeException(
            sprintf(
                "Error %d: %s",
                $errorNo,
                $error
            )
        );
    }

    $data = @json_decode($result, true);

    if ($errNo = json_last_error()) {
        echo $result . PHP_EOL;
        exit;
        throw new \JsonException(json_last_error_msg() . "\n\n" . $result, $errNo);
    }

    if (isset($data['errors'])) {
        print_r($data['errors']);exit;
        throw new \RuntimeException(
            sprintf('%d GraphQL Error(s)', count($data['errors'])) . PHP_EOL . print_r(implode(PHP_EOL, array_column($data['errors'], 'debugMessage')), true)
        );
    }

    return $data;
}
