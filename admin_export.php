<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_admin_page();

$type = $_GET['type'] ?? '';
if (!in_array($type, ['orders', 'customers', 'reviews'], true)) {
    http_response_code(400); exit('Unsupported export type.');
}
$validDate = static function (mixed $value): ?string {
    if (!is_string($value) || $value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
};
$from = $validDate($_GET['from'] ?? null) ?? '2000-01-01';
$to = $validDate($_GET['to'] ?? null) ?? date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
$queries = [
 'orders' => ["SELECT o.order_number,o.order_date,u.name,o.payment_method,o.status,o.total FROM orders o JOIN users u ON u.id=o.user_id WHERE o.order_date BETWEEN ? AND ? ORDER BY o.order_date DESC", ['Order number','Date','Customer','Payment method','Status','Total (RM)']],
 'customers' => ["SELECT username,name,email,phone,member_since FROM users WHERE role='user' AND CONCAT(member_since,' 00:00:00') BETWEEN ? AND ? ORDER BY member_since DESC", ['Username','Name','Email','Phone','Member since']],
 'reviews' => ["SELECT u.name,r.rating,r.review,r.created_at FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.created_at BETWEEN ? AND ? ORDER BY r.created_at DESC", ['Customer','Rating','Review','Created at']]
];
[$sql,$headings]=$queries[$type]; $start=$from.' 00:00:00';$end=$to.' 23:59:59';
$statement=db()->prepare($sql);$statement->bind_param('ss',$start,$end);$statement->execute();$rows=$statement->get_result();
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="greensprout-'.$type.'-'.$from.'-to-'.$to.'.csv"');
header('Cache-Control: no-store');
$output=fopen('php://output','wb');fwrite($output,"\xEF\xBB\xBF");fputcsv($output,$headings);
$safe = static fn($value) => is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
while($row=$rows->fetch_row())fputcsv($output,array_map($safe,$row));fclose($output);
