# PCP 通知 API 仕様書(C-001 で使う範囲)

PCP(PractiCase Cloud Platform)は、教材内で完結するローカルの通知基盤。
`app` コンテナと同じ Docker ネットワーク上の別コンテナで動いており、
**ホスト(あなたのPC)からは直接つながらない**。必ず `app` コンテナの中から呼ぶ:

```text
docker compose exec app curl http://pcp:8080/v1/health
```

- ベースURL: `http://pcp:8080`(`app` コンテナ内からのみ)
- 教材用テストキー: `PCP_TEST_KEY_a1b2c3d4e5f60718`(ダミー。実在のキーではない)

## 認証

`/v1/health` 以外のすべてのエンドポイントは、リクエストヘッダーでの認証が必要:

```text
Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718
```

キーが無い・間違っている場合は `401` と `INVALID_API_KEY` が返る。

## エンドポイント

| メソッド | パス | 認証 | 用途 |
|---|---|---|---|
| GET | `/v1/health` | 不要 | 疎通確認 |
| POST | `/v1/notifications` | 必要 | 通知を1件作成する(送信を試みる) |
| GET | `/v1/notifications/{id}` | 必要 | 作成した通知の状態を確認する |
| GET | `/v1/audit-log` | 必要 | 監査ログ(操作の記録)を照会する |

### POST /v1/notifications

リクエストボディ(JSON):

```json
{
  "recipient": "test-user-001",
  "message": "応募ステータスが更新されました"
}
```

curl での例(1行。PowerShell 7系・Git Bash 共通):

```text
docker compose exec app curl -s -X POST http://pcp:8080/v1/notifications -H "Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718" -H "Content-Type: application/json" -d '{"recipient":"test-user-001","message":"応募ステータスが更新されました"}'
```

JSON 部分は**外側をシングルクォートで囲む**。JSON の中の二重引用符を
バックスラッシュ(`\"`)でエスケープする書き方は、シェルによって崩れるため使わない。

レスポンス(成功時 `201`):

```json
{
  "id": "ntf_000001",
  "status": "queued",
  "recipient": "test-user-001",
  "created_at": "2026-07-08T12:00:00Z"
}
```

`recipient` と `message` のどちらかが欠けていると `400`(`INVALID_REQUEST`)。

### GET /v1/notifications/{id}

作成時に返った `id` を使う。レスポンスは作成時と同じ形で、`status` だけが変わる。

**状態の遷移**: `status` は作成直後は `queued`(受付済み)。**作成から約2秒後**に、
- 宛先が `fail-` で始まる通知 → `failed`(配送失敗)
- それ以外 → `delivered`(配送済み)

へ変わる(参照したタイミングで評価される。待ってから再度 GET すること)。
存在しない ID を指定すると `404`(`NOT_FOUND`)。

### GET /v1/audit-log

PCP が受け付けたリクエストの記録(監査ログ)を新しい順に返す。

絞り込み(すべて任意・組み合わせ可):

| パラメータ | 意味 |
|---|---|
| `request_id` | 特定の1記録に絞る |
| `api_key_suffix` | API キーの**末尾4桁**で絞る(全文は指定できない) |
| `event_type` | `SUCCESS` またはエラーコード(`INVALID_API_KEY` 等)で絞る |
| `limit` | 件数上限(既定50・最大200) |

レスポンス例:

```json
{
  "entries": [
    {
      "request_id": "req_000123",
      "timestamp": "2026-07-08T12:00:00Z",
      "api_key_suffix": "0718",
      "method": "POST",
      "path": "/v1/notifications",
      "event_type": "SUCCESS",
      "http_status": 201,
      "recipient": "test-user-001"
    }
  ],
  "count": 1
}
```

**監査ログの設計(この課題の観察ポイント)**:

- API キーは**末尾4桁だけ**が記録される。全文はどこにも残らない
  (ログが流出しても、キー本体は漏れないようにするため)
- `/v1/health` と `/v1/audit-log` 自身へのアクセスは記録されない
  (定期的な疎通確認や「記録を見る」操作でログが埋まるのを防ぐため)

## エラーレスポンス(共通形式)

```json
{
  "error": {
    "code": "INVALID_API_KEY",
    "message": "APIキーが無効です"
  }
}
```

| HTTP | code | 状況 |
|---|---|---|
| 400 | `INVALID_REQUEST` | 必須項目(`recipient` / `message`)の欠落 |
| 401 | `INVALID_API_KEY` | API キーが無い・間違っている |
| 404 | `NOT_FOUND` | 存在しない通知 ID・存在しないパス |

## 画面での観察(任意)

admin でログインすると、admin の監査ログ画面(`/admin/pcp-audit-log.php`)で、
curl で照会したのと同じ記録を画面でも確認できる。この課題では、
自分が送った通知の SUCCESS(201)の記録を探してみる。
