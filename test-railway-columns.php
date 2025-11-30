<?php
// Verificar columnas en Railway
$remotePdo = new PDO('mysql:host=nozomi.proxy.rlwy.net;port=50599;dbname=railway;charset=utf8mb4', 'root', 'SbPLDWRfjsRUtVHbxRBURYqktfpCQTlo');
$result = $remotePdo->query('DESCRIBE propuestas')->fetchAll(PDO::FETCH_ASSOC);
$cols = array_column($result, 'Field');
echo 'Columnas en Railway propuestas: ' . implode(', ', $cols) . PHP_EOL;
echo 'archivada existe: ' . (in_array('archivada', $cols) ? 'SÍ' : 'NO') . PHP_EOL;
