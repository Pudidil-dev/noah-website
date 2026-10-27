<?php
declare(strict_types=1);

const ORDER_STATUSES = ['pending', 'paid', 'cancelled'];
const REVIEW_STATUSES = ['pending', 'approved', 'rejected'];

function order_list(PDO $pdo, ?int $eventId = null, ?string $status = null): array
{
    $conditions = []; $params = [];
    if ($eventId !== null) { $conditions[] = 'o.event_id = :event'; $params['event'] = $eventId; }
    if ($status !== null) { $conditions[] = 'o.status = :status'; $params['status'] = $status; }
    return fetch_all($pdo, 'SELECT o.*, e.title AS event_title FROM ticket_orders o JOIN events e ON e.id=o.event_id'
        . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY o.created_at DESC LIMIT 200', $params);
}
function order_set_status(PDO $pdo, int $id, string $status): bool
{
    if (!in_array($status, ORDER_STATUSES, true)) return false;
    $s=$pdo->prepare('UPDATE ticket_orders SET status=:status WHERE id=:id');
    $s->execute(compact('status','id'));
    return $s->rowCount()===1;
}
function order_delete(PDO $pdo, int $id): void { $pdo->prepare('DELETE FROM ticket_orders WHERE id=?')->execute([$id]); }
function submission_list(PDO $pdo, string $status): array
{
    return fetch_all($pdo, 'SELECT s.*,c.city FROM fan_submissions s LEFT JOIN communities c ON c.id=s.community_id WHERE s.status=:status ORDER BY s.created_at DESC LIMIT 200', ['status'=>$status]);
}
function submission_review(PDO $pdo, int $id, string $status, int $adminId): bool
{
    if (!in_array($status,['approved','rejected'],true)) return false;
    $stmt=$pdo->prepare('UPDATE fan_submissions SET status=:status, reviewed_by=:admin WHERE id=:id');
    $stmt->execute(['status'=>$status,'admin'=>$adminId,'id'=>$id]);
    return $stmt->rowCount()===1;
}
function submission_delete(PDO $pdo, int $id): void { $pdo->prepare('DELETE FROM fan_submissions WHERE id=?')->execute([$id]); }
function setlist_items(PDO $pdo, int $eventId): array
{
    return fetch_all($pdo,'SELECT s.position,s.is_encore,t.id AS track_id,t.title,a.title AS album FROM setlist_items s JOIN tracks t ON t.id=s.track_id LEFT JOIN albums a ON a.id=t.album_id WHERE s.event_id=:event ORDER BY s.position',['event'=>$eventId]);
}
function setlist_add(PDO $pdo, int $eventId, int $trackId, bool $isEncore): void
{
    with_transaction($pdo, function(PDO $pdo) use($eventId,$trackId,$isEncore):void {
        $pdo->prepare('SELECT id FROM events WHERE id=? FOR UPDATE')->execute([$eventId]);
        $position=(int)(fetch_one($pdo,'SELECT COALESCE(MAX(position),0)+1 AS p FROM setlist_items WHERE event_id=?',[$eventId])['p'] ?? 1);
        $pdo->prepare('INSERT INTO setlist_items(event_id,position,track_id,is_encore) VALUES (?,?,?,?)')->execute([$eventId,$position,$trackId,db_param($isEncore)]);
    });
}
function setlist_remove(PDO $pdo, int $eventId, int $position): void
{
    if ($eventId < 1 || $position < 1) {
        throw new InvalidArgumentException('Event dan posisi setlist harus positif.');
    }
    with_transaction($pdo,function(PDO $pdo) use($eventId,$position):void {
        $pdo->prepare('SELECT id FROM events WHERE id=? FOR UPDATE')->execute([$eventId]);
        $delete = $pdo->prepare('DELETE FROM setlist_items WHERE event_id=? AND position=?');
        $delete->execute([$eventId,$position]);
        if ($delete->rowCount() === 0) {
            return;
        }
        $positions = fetch_all($pdo, 'SELECT position FROM setlist_items WHERE event_id=? AND position>? ORDER BY position', [$eventId,$position]);
        $move = $pdo->prepare('UPDATE setlist_items SET position=? WHERE event_id=? AND position=?');
        foreach ($positions as $item) {
            $current = (int)$item['position'];
            $move->execute([$current - 1, $eventId, $current]);
        }
    });
}
