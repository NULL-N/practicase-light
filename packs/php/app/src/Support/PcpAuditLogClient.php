<?php

declare(strict_types=1);

namespace App\Support;

/**
 * PCP監査ログの読み取りクライアント(admin の API監査ログ画面用)。
 *
 * 設計の約束:
 * - **読み取り専用**。書き込み(POST)は持たない
 * - 使うキーは**閲覧用キー(末尾3333)** — audit-log照会は認証のみで
 *   書き込み権限も要らない。フル権限キーを使わない(C-003で教える最小権限の実践)
 * - タイムアウトは短く(2秒)。PCPの不調でadmin画面を待たせない
 * - 失敗時は短いエラー文言だけを返し、キー・レスポンス全文を呼び出し側へ渡さない
 *   (観察窓を秘密や生データの漏洩口にしない)
 */
final class PcpAuditLogClient
{
    public function __construct(
        private readonly string $baseUrl = 'http://pcp:8080',
        private readonly string $apiKey = 'PCP_TEST_KEY_readonly00003333', // 教材用ダミー(閲覧用)
    ) {
    }

    /**
     * 監査ログを新しい順で取得する。絞り込みは既存APIのパラメータのみ。
     *
     * @return array{ok: bool, entries: array<int, array<string, mixed>>, error: string}
     */
    public function fetchEntries(string $requestId, string $apiKeySuffix, string $eventType, int $limit): array
    {
        $query = ['limit' => (string) max(1, min($limit, 200))];
        if ($requestId !== '') {
            $query['request_id'] = $requestId;
        }
        if ($apiKeySuffix !== '') {
            $query['api_key_suffix'] = $apiKeySuffix;
        }
        if ($eventType !== '') {
            $query['event_type'] = $eventType;
        }
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $this->apiKey,
            'ignore_errors' => true, // 4xx/5xx でもステータスを読む
            'timeout' => 2,          // PCP の不調で admin 画面を待たせない
        ]]);
        $raw = @file_get_contents($this->baseUrl . '/v1/audit-log?' . http_build_query($query), false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $line) { // 接続失敗時は未定義のため ?? []
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m) === 1) {
                $status = (int) $m[1];
            }
        }
        if ($raw === false) {
            return ['ok' => false, 'entries' => [], 'error' => 'PCPに接続できません(docker compose up -d で pcp サービスが起動しているか確認してください)'];
        }
        if ($status !== 200) {
            return ['ok' => false, 'entries' => [], 'error' => "PCPの監査ログ照会が失敗しました(HTTP {$status})"];
        }
        $json = json_decode((string) $raw, true);
        $entries = is_array($json) && is_array($json['entries'] ?? null) ? $json['entries'] : null;
        if ($entries === null) {
            return ['ok' => false, 'entries' => [], 'error' => 'PCPの応答を読み取れませんでした'];
        }

        return ['ok' => true, 'entries' => array_values(array_filter($entries, 'is_array')), 'error' => ''];
    }
}
