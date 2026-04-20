<?php
require_once '../config/config.php';
checkRole('admin');

header('Content-Type: application/json');

$filter_product = $_GET['filter_product'] ?? '';
$filter_jenis = $_GET['filter_jenis'] ?? '';
$filter_date_from = $_GET['filter_date_from'] ?? '';
$filter_date_to = $_GET['filter_date_to'] ?? '';

$where = "1=1";
$params = [];
$types = '';

if ($filter_product) {
    $where .= " AND sm.product_id = ?";
    $params[] = (int)$filter_product;
    $types .= 'i';
}
if ($filter_jenis) {
    $where .= " AND sm.jenis = ?";
    $params[] = $filter_jenis;
    $types .= 's';
}
if ($filter_date_from) {
    $where .= " AND DATE(sm.tanggal) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}
if ($filter_date_to) {
    $where .= " AND DATE(sm.tanggal) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

$query = "SELECT sm.*, p.nama_produk, p.kategori 
          FROM stock_movements sm
          JOIN products p ON sm.product_id = p.id
          WHERE $where
          ORDER BY sm.tanggal DESC
          LIMIT 100";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

mysqli_stmt_close($stmt);

echo json_encode($data);
?>