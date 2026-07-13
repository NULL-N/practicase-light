<?php

declare(strict_types=1);

/**
 * PCP(PractiCase Cloud Platform)モックサーバー — Light 版
 *
 * 教材の中だけで完結する架空のクラウド通知API。本物の外部サービスへは一切通信しない。
 * php -S のルータースクリプトとして起動する:
 *   php -S 0.0.0.0:8080 server.php
 *
 * Light 版の範囲(この教材で扱う機能だけを持つ):
 * - 認証(Bearer キー・401)/ 権限スコープ(403)/ 通知の作成・参照 / 監査ログ
 * - これ以外の高度な保護機能は持たない。監査ログ(audit_log)の列構成は
 *   上位版とそろえてあり、記録の読み方は同じ
 *
 * 安全上の約束(教材全体の方針):
 * - APIキーは誰が見てもダミーと分かる PCP_TEST_KEY_ 形式のみ。本物の秘密情報は存在しない
 * - 監査ログにAPIキーの全文は記録しない(キーは末尾4桁のみ)
 * - /v1/health と /v1/audit-log 自身へのアクセスは監査ログに記録しない
 *   (ヘルスチェックの定期実行や、記録を確認する操作そのものでログが埋まるのを防ぐ)
 */

// 教材用テストキー(ダミー)。学習者に配る正規のキー:
//   フル権限(通知の作成・参照): PCP_TEST_KEY_a1b2c3d4e5f60718
//   閲覧用(参照のみ。通知の作成は 403): PCP_TEST_KEY_readonly00003333
const PCP_VALID_API_KEYS = ['PCP_TEST_KEY_a1b2c3d4e5f60718', 'PCP_TEST_KEY_readonly00003333'];
// 通知を作成(POST /v1/notifications)できるキー。ここに無い有効キーは認証は通るが
// 403 INSUFFICIENT_SCOPE になる(認証=401と認可=403を別々に体験させるための単段スコープ)
const PCP_NOTIFY_WRITE_KEYS = ['PCP_TEST_KEY_a1b2c3d4e5f60718'];

const PCP_DB_PATH = '/var/pcp-data/pcp.sqlite';
// 通知は作成から一定秒数の経過後に、配送結果へ遷移する(GET時に評価する)
const PCP_DELIVER_AFTER_SECONDS = 2;
// 宛先がこの接頭辞で始まる通知は、配送失敗(failed)になる
const PCP_FAIL_RECIPIENT_PREFIX = 'fail-';
const PCP_AUDIT_DEFAULT_LIMIT = 50;
const PCP_AUDIT_MAX_LIMIT = 200;

function pcpPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dir = dirname(PCP_DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $pdo = new PDO('sqlite:' . PCP_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications ('
        . ' seq INTEGER PRIMARY KEY AUTOINCREMENT,'
        . ' id TEXT NOT NULL UNIQUE,'
        . ' recipient TEXT NOT NULL,'
        . ' message TEXT NOT NULL,'
        . ' created_epoch INTEGER NOT NULL)'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS audit_log ('
        . ' seq INTEGER PRIMARY KEY AUTOINCREMENT,'
        . ' request_id TEXT NOT NULL UNIQUE,'
        . ' logged_at TEXT NOT NULL,'
        . ' api_key_suffix TEXT,'
        . ' method TEXT NOT NULL,'
        . ' path TEXT NOT NULL,'
        . ' event_type TEXT NOT NULL,'
        . ' http_status INTEGER NOT NULL,'
        . ' recipient TEXT,'
        . ' delay_ms INTEGER,'
        . ' target TEXT)'
    );
    // delay_ms / target 列は上位版と列構成をそろえるための列(Light 版では常に null)。
    // 旧スキーマの既存 volume にも冪等に列を足す(列が既にあれば失敗するので無視する)。
    // volume を作り直させない = 学習者の過去の監査ログを消さない
    try {
        $pdo->exec('ALTER TABLE audit_log ADD COLUMN delay_ms INTEGER');
    } catch (Throwable) {
        // 列が既に存在する
    }
    try {
        $pdo->exec('ALTER TABLE audit_log ADD COLUMN target TEXT');
    } catch (Throwable) {
        // 列が既に存在する
    }
    // 照会(api_key_suffix / event_type 絞り込み)が使う複合index。
    // IF NOT EXISTS で冪等 — 既存 volume でも新規でも安全
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_audit_key_event ON audit_log(api_key_suffix, event_type, logged_at)');

    return $pdo;
}

function pcpJson(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
}

function pcpErrorBody(string $code, string $message): array
{
    return ['error' => ['code' => $code, 'message' => $message]];
}

// Authorization: Bearer <key> からキーを取り出す(無ければ null)
function pcpPresentedKey(): ?string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp((string) $name, 'Authorization') === 0) {
                $header = (string) $value;
                break;
            }
        }
    }
    if (preg_match('/^Bearer\s+(\S+)$/', $header, $m) === 1) {
        return $m[1];
    }

    return null;
}

// 監査ログにはキーの末尾4桁だけを残す(全文は絶対に記録しない)
function pcpKeySuffix(?string $key): ?string
{
    if ($key === null || $key === '') {
        return null;
    }

    return substr($key, -4);
}

function pcpIso(int $epoch): string
{
    return gmdate('Y-m-d\TH:i:s\Z', $epoch);
}

function pcpNextSeq(string $table): int
{
    $max = pcpPdo()->query("SELECT COALESCE(MAX(seq), 0) FROM {$table}")->fetchColumn();

    return (int) $max + 1;
}

function pcpAudit(string $method, string $path, ?string $key, string $eventType, int $httpStatus, ?string $recipient, ?int $delayMs = null): void
{
    $seq = pcpNextSeq('audit_log');
    $stmt = pcpPdo()->prepare(
        'INSERT INTO audit_log (seq, request_id, logged_at, api_key_suffix, method, path, event_type, http_status, recipient, delay_ms)'
        . ' VALUES (:seq, :request_id, :logged_at, :suffix, :method, :path, :event_type, :http_status, :recipient, :delay_ms)'
    );
    $stmt->execute([
        ':seq' => $seq,
        ':request_id' => sprintf('req_%06d', $seq),
        ':logged_at' => pcpIso(time()),
        ':suffix' => pcpKeySuffix($key),
        ':method' => $method,
        ':path' => $path,
        ':event_type' => $eventType,
        ':http_status' => $httpStatus,
        ':recipient' => $recipient,
        ':delay_ms' => $delayMs,
    ]);
}

// 通知の状態は保存せず、参照時に作成時刻から評価する(遷移は決定的 — 乱数もワーカーも使わない)
function pcpStatusOf(string $recipient, int $createdEpoch): string
{
    if (time() - $createdEpoch < PCP_DELIVER_AFTER_SECONDS) {
        return 'queued';
    }

    return str_starts_with($recipient, PCP_FAIL_RECIPIENT_PREFIX) ? 'failed' : 'delivered';
}

function pcpHandleAuditLogQuery(): void
{
    $where = [];
    $params = [];
    foreach (['request_id', 'api_key_suffix', 'event_type'] as $column) {
        $value = $_GET[$column] ?? null;
        if (is_string($value) && $value !== '') {
            $where[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }
    }
    $limit = (int) ($_GET['limit'] ?? PCP_AUDIT_DEFAULT_LIMIT);
    $limit = max(1, min($limit, PCP_AUDIT_MAX_LIMIT));
    $sql = 'SELECT request_id, logged_at, api_key_suffix, method, path, event_type, http_status, recipient, delay_ms FROM audit_log';
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= " ORDER BY seq DESC LIMIT {$limit}";
    $stmt = pcpPdo()->prepare($sql);
    $stmt->execute($params);
    $entries = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $entries[] = [
            'request_id' => $row['request_id'],
            'timestamp' => $row['logged_at'],
            'api_key_suffix' => $row['api_key_suffix'],
            'method' => $row['method'],
            'path' => $row['path'],
            'event_type' => $row['event_type'],
            'http_status' => (int) $row['http_status'],
            'recipient' => $row['recipient'],
            'delay_ms' => $row['delay_ms'] === null ? null : (int) $row['delay_ms'],
        ];
    }
    pcpJson(200, ['entries' => $entries, 'count' => count($entries)]);
}

// ---- リクエスト処理 ----

$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$key = pcpPresentedKey();
$authorized = $key !== null && in_array($key, PCP_VALID_API_KEYS, true);

try {
    // 疎通確認(認証不要・監査ログ対象外)
    if ($method === 'GET' && $path === '/v1/health') {
        pcpJson(200, ['status' => 'ok']);

        return;
    }

    // 監査ログの照会(要認証・監査ログ対象外)
    if ($method === 'GET' && $path === '/v1/audit-log') {
        if (!$authorized) {
            pcpJson(401, pcpErrorBody('INVALID_API_KEY', 'APIキーが無効です'));

            return;
        }
        pcpHandleAuditLogQuery();

        return;
    }

    // ここから先は認証必須・全リクエストを監査ログに記録する
    if (!$authorized) {
        pcpAudit($method, $path, $key, 'INVALID_API_KEY', 401, null);
        pcpJson(401, pcpErrorBody('INVALID_API_KEY', 'APIキーが無効です'));

        return;
    }

    // 通知の作成(送信を試みる)
    if ($method === 'POST' && $path === '/v1/notifications') {
        // 認可(スコープ)の検査。認証(401)より後・入力検証(400)より先に評価する
        if (!in_array($key, PCP_NOTIFY_WRITE_KEYS, true)) {
            pcpAudit($method, $path, $key, 'INSUFFICIENT_SCOPE', 403, null);
            pcpJson(403, pcpErrorBody('INSUFFICIENT_SCOPE', 'このAPIキーには通知を作成する権限がありません'));

            return;
        }
        $data = json_decode((string) file_get_contents('php://input'), true);
        $recipient = is_array($data) && is_string($data['recipient'] ?? null) ? trim($data['recipient']) : '';
        $message = is_array($data) && is_string($data['message'] ?? null) ? trim($data['message']) : '';
        if ($recipient === '' || $message === '') {
            pcpAudit($method, $path, $key, 'INVALID_REQUEST', 400, $recipient !== '' ? $recipient : null);
            pcpJson(400, pcpErrorBody('INVALID_REQUEST', 'recipient と message は必須です'));

            return;
        }
        $seq = pcpNextSeq('notifications');
        $id = sprintf('ntf_%06d', $seq);
        $createdEpoch = time();
        $stmt = pcpPdo()->prepare(
            'INSERT INTO notifications (seq, id, recipient, message, created_epoch)'
            . ' VALUES (:seq, :id, :recipient, :message, :created_epoch)'
        );
        $stmt->execute([
            ':seq' => $seq,
            ':id' => $id,
            ':recipient' => $recipient,
            ':message' => $message,
            ':created_epoch' => $createdEpoch,
        ]);
        pcpAudit($method, $path, $key, 'SUCCESS', 201, $recipient);
        pcpJson(201, [
            'id' => $id,
            'status' => 'queued',
            'recipient' => $recipient,
            'created_at' => pcpIso($createdEpoch),
        ]);

        return;
    }

    // 通知の状態確認
    if ($method === 'GET' && preg_match('#^/v1/notifications/([A-Za-z0-9_\-]+)$#', $path, $m) === 1) {
        $stmt = pcpPdo()->prepare('SELECT id, recipient, created_epoch FROM notifications WHERE id = :id');
        $stmt->execute([':id' => $m[1]]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            pcpAudit($method, $path, $key, 'NOT_FOUND', 404, null);
            pcpJson(404, pcpErrorBody('NOT_FOUND', '指定された通知は存在しません'));

            return;
        }
        pcpAudit($method, $path, $key, 'SUCCESS', 200, null);
        pcpJson(200, [
            'id' => $row['id'],
            'status' => pcpStatusOf((string) $row['recipient'], (int) $row['created_epoch']),
            'recipient' => $row['recipient'],
            'created_at' => pcpIso((int) $row['created_epoch']),
        ]);

        return;
    }

    // 未定義のエンドポイント
    pcpAudit($method, $path, $key, 'NOT_FOUND', 404, null);
    pcpJson(404, pcpErrorBody('NOT_FOUND', '存在しないエンドポイントです'));
} catch (Throwable $e) {
    try {
        pcpAudit($method, $path, $key, 'INTERNAL_ERROR', 500, null);
    } catch (Throwable) {
        // 監査ログ自体が書けない状態でも、応答だけは返す
    }
    pcpJson(500, pcpErrorBody('INTERNAL_ERROR', 'サーバー内部でエラーが発生しました'));
}
