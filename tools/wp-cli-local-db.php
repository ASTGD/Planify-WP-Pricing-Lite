<?php
// Adjust MySQL client defaults for Local when running via WP-CLI.
// This helps WP-CLI connect to the Local-managed MySQL using the socket/port
// exposed under ~/Library/Application Support/Local/run/<site-id>/mysql.

if ( php_sapi_name() !== 'cli' ) {
    return;
}

// Only run when invoking WP-CLI (avoid affecting other CLI scripts).
if ( ! defined( 'WP_CLI' ) ) {
    return;
}

$base = getenv('HOME') . '/Library/Application Support/Local/run/8WWRD_xr0/mysql';
$socket = $base . '/mysqld.sock';
$port   = 10004; // Read from Local's generated my.cnf for this site

// Prefer socket if available; otherwise fall back to TCP port.
if ( is_readable( $socket ) ) {
    @ini_set( 'mysqli.default_socket', $socket );
    // Also hint PDO MySQL
    @ini_set( 'pdo_mysql.default_socket', $socket );
}

@ini_set( 'mysqli.default_port', (string) $port );
@ini_set( 'mysqli.default_host', '127.0.0.1' );

// Some client libraries respect environment variables
putenv( 'MYSQL_UNIX_PORT=' . $socket );
putenv( 'MYSQL_TCP_PORT=' . $port );

