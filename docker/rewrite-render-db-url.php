<?php

/**
 * Render injects the Internal Database URL (host = "dpg-…-a") when a Postgres
 * instance is linked. That hostname only resolves on the private network of
 * the SAME region. If the web service and the database are in different
 * regions, migrate dies with: could not translate host name "dpg-…-a".
 *
 * When the internal host does not resolve, try the public
 * {host}.{region}-postgres.render.com endpoints until PDO connects.
 *
 * Prints the (possibly rewritten) URL on stdout. Logs go to stderr.
 */

$url = getenv('DB_URL') ?: getenv('DATABASE_URL') ?: '';
if ($url === '') {
    fwrite(STDERR, "ERROR: DB_URL (or DATABASE_URL) is not set.\n");
    exit(1);
}

$parts = parse_url($url);
if (! is_array($parts) || empty($parts['host'])) {
    echo $url, PHP_EOL;
    exit(0);
}

$host = $parts['host'];

if (str_contains($host, '.')) {
    echo $url, PHP_EOL;
    exit(0);
}

$resolved = static function (string $name): bool {
    $ip = gethostbyname($name);

    return is_string($ip) && $ip !== '' && $ip !== $name;
};

if ($resolved($host)) {
    echo $url, PHP_EOL;
    exit(0);
}

$user = $parts['user'] ?? '';
$pass = $parts['pass'] ?? '';
$port = (int) ($parts['port'] ?? 5432);
$dbName = ltrim($parts['path'] ?? '', '/');
$dbName = explode('?', $dbName)[0];
if ($dbName === '') {
    $dbName = 'rdp';
}

$suffixes = [
    'frankfurt-postgres.render.com',
    'oregon-postgres.render.com',
    'ohio-postgres.render.com',
    'virginia-postgres.render.com',
    'singapore-postgres.render.com',
];

$preferred = strtolower(trim((string) getenv('RENDER_POSTGRES_REGION')));
if ($preferred !== '') {
    array_unshift($suffixes, $preferred.'-postgres.render.com');
    $suffixes = array_values(array_unique($suffixes));
}

$buildUrl = static function (string $newHost) use ($parts, $user, $pass, $port): string {
    $scheme = $parts['scheme'] ?? 'postgres';
    $auth = '';
    if ($user !== '') {
        $auth = rawurlencode($user);
        if ($pass !== '') {
            $auth .= ':'.rawurlencode($pass);
        }
        $auth .= '@';
    }
    $path = $parts['path'] ?? '';
    $query = [];
    if (! empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['sslmode'] = $query['sslmode'] ?? 'require';

    return $scheme.'://'.$auth.$newHost.':'.$port.$path.'?'.http_build_query($query);
};

$canConnect = static function (string $candidate) use ($user, $pass, $port, $dbName): bool {
    if (! extension_loaded('pdo_pgsql')) {
        return $candidate !== '';
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;sslmode=require;connect_timeout=4',
        $candidate,
        $port,
        $dbName
    );

    try {
        new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        return true;
    } catch (Throwable $e) {
        fwrite(STDERR, '  skip '.$candidate.': '.strtok($e->getMessage(), "\n")."\n");

        return false;
    }
};

fwrite(STDERR, "WARNING: Internal Postgres host \"{$host}\" did not resolve (web service and database are probably in different regions).\n");
fwrite(STDERR, "Trying public Render hostnames…\n");

foreach ($suffixes as $suffix) {
    $candidate = $host.'.'.$suffix;
    if ($canConnect($candidate)) {
        fwrite(STDERR, "Using public host {$candidate} (sslmode=require).\n");
        echo $buildUrl($candidate), PHP_EOL;
        exit(0);
    }
}

fwrite(STDERR, "ERROR: Could not open Render Postgres on any public hostname.\n");
fwrite(STDERR, "Dashboard: rdp-db → Connect → copy External Database URL into rdp-web → Environment → DB_URL. Set DB_SSLMODE=require. Save → Manual Deploy.\n");
fwrite(STDERR, "If rdp-db is Expired (free plan, 30 days), upgrade it first.\n");
exit(1);
