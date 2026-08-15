import subprocess

php_code = """<?php
$sockets = [
    '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock',
    '/tmp/mysql.sock',
    '/var/mysql/mysql.sock'
];

echo "Testing MySQL connections from XAMPP PHP:\\n";

foreach ($sockets as $sock) {
    if (file_exists($sock)) {
        echo "Found socket file: $sock\\n";
        try {
            $pdo = new PDO("mysql:unix_socket=$sock;dbname=mehedih3_cpro306_g1;charset=utf8mb4", "root", "");
            echo "  SUCCESS: Connected to mehedih3_cpro306_g1 via $sock (root/'')\\n";
        } catch (Exception $e) {
            echo "  Failed with root/'': " . $e->getMessage() . "\\n";
        }
        try {
            $pdo = new PDO("mysql:unix_socket=$sock;dbname=mehedih3_cpro306_g1;charset=utf8mb4", "mehedih3_cpro306_g1", "cpro306");
            echo "  SUCCESS: Connected to mehedih3_cpro306_g1 via $sock (mehedih3_cpro306_g1/cpro306)\\n";
        } catch (Exception $e) {
            echo "  Failed with user/pass: " . $e->getMessage() . "\\n";
        }
    } else {
        echo "Socket not found: $sock\\n";
    }
}

// Test TCP 127.0.0.1:3306
echo "\\nTesting TCP 127.0.0.1:3306:\\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=mehedih3_cpro306_g1;charset=utf8mb4", "root", "", [PDO::ATTR_TIMEOUT => 2]);
    echo "  SUCCESS: Connected via 127.0.0.1:3306 (root/'')\\n";
} catch (Exception $e) {
    echo "  Failed via 127.0.0.1:3306: " . $e->getMessage() . "\\n";
}
"""

with open('test_local_db.php', 'w') as f:
    f.write(php_code)

res = subprocess.run(['/Applications/XAMPP/xamppfiles/bin/php', 'test_local_db.php'], capture_output=True, text=True)
print(res.stdout)
print(res.stderr)
