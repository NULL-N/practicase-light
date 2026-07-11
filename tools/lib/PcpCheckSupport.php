<?php

declare(strict_types=1);

/**
 * PCP(教材内のローカル架空クラウドAPI)を check から検証するための共通ヘルパー。
 *
 * 設計上の約束(すべてのC系checkが守る不変条件):
 * - check は学習者の提出物(コマンド・スクリプト)を実行しない。check 自身が
 *   固定URL(PCP_BASE_URL)へリクエストを送り、応答と監査ログを直接検証する
 * - **check は 401・403 などの認証・認可エラーを自分では発生させない**
 *   (無効キーでの送信も、権限の無いキーでの書き込みも、probe では行わない)。
 *   監査ログ上の INVALID_API_KEY / INSUFFICIENT_SCOPE 記録を
 *   「学習者が実際に失敗系を試した証拠」として使う課題があるため、
 *   check がこれらを作ると判定が自家中毒で成立しなくなる。probe の送信(POST)は
 *   フル権限キーだけで行う。
 *   監査ログに記録されない操作(/v1/health・/v1/audit-log の照会)は、
 *   どのキーで行ってもよい(audit-log は Bearer 認証のみ)
 * - probe(checkの自動送信)の宛先は 'check-c' で始める(isCheckProbe と対)。
 *   学習者の操作の判定では isCheckProbe で probe を除外する
 */

namespace PractiCase\Pcp;

const PCP_BASE_URL = 'http://pcp:8080'; // 固定。学習者の入力・提出物からは変更できない
const PCP_API_KEY = 'PCP_TEST_KEY_a1b2c3d4e5f60718';

/** @return array{0:int,1:mixed,2:string} [HTTPステータス, JSONデコード結果(失敗ならnull), 生ボディ] */
function pcpRequest(string $method, string $path, ?string $key, ?array $body = null): array
{
    $headers = [];
    if ($key !== null) {
        $headers[] = 'Authorization: Bearer ' . $key;
    }
    $payload = '';
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $payload = (string) json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $payload,
        'ignore_errors' => true,
        'timeout' => 5,
    ]]);
    $raw = @file_get_contents(PCP_BASE_URL . $path, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $headerLine) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $headerLine, $m) === 1) {
            $status = (int) $m[1];
        }
    }
    $rawText = is_string($raw) ? $raw : '';

    return [$status, json_decode($rawText, true), $rawText];
}

/**
 * 監査ログを新しい順で取得する(照会自体はPCP側で記録されない)。失敗時は空配列。
 * $eventType / $apiKeySuffix を指定すると、サーバー側で絞り込んでから limit を適用する —
 * 監査ログが増えても、対象の記録が limit の窓から押し出されにくくなる(飽和耐性)。
 *
 * @return array<int, array<string, mixed>>
 */
function pcpAuditEntries(int $limit = 200, ?string $eventType = null, ?string $apiKeySuffix = null): array
{
    $query = '/v1/audit-log?limit=' . $limit;
    if ($eventType !== null) {
        $query .= '&event_type=' . rawurlencode($eventType);
    }
    if ($apiKeySuffix !== null) {
        $query .= '&api_key_suffix=' . rawurlencode($apiKeySuffix);
    }
    [$status, $json] = pcpRequest('GET', $query, PCP_API_KEY);
    if ($status !== 200 || !is_array($json)) {
        return [];
    }
    $entries = $json['entries'] ?? [];

    return is_array($entries) ? array_values(array_filter($entries, 'is_array')) : [];
}

/** 課題IDで始まる報告書(reports/<課題ID>*.md)を連結して返す */
function pcpNote(string $ticketId): string
{
    $content = '';
    foreach (glob('reports/' . $ticketId . '*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

/** checkの自動送信(probe)の宛先かどうか。各checkのprobe宛先は 'check-c' で始める */
function isCheckProbe(string $recipient): bool
{
    return str_starts_with($recipient, 'check-c');
}
