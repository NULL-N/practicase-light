# PCP 権限スコープ仕様(C-003 で使う範囲)

C-002 までの仕様(認証と401)を前提に、この課題では**スコープ(権限)と403**を扱う。

- ベースURL: `http://pcp:8080`(`app` コンテナ内からのみ)
- フル権限キー: `PCP_TEST_KEY_a1b2c3d4e5f60718`(通知の作成・参照)
- **閲覧用キー**: `PCP_TEST_KEY_readonly00003333`(参照のみ。**通知の作成は不可**)

## スコープ(権限)の仕組み

PCP の API キーには「できること」が紐づいている:

| キー | 通知の参照(GET) | 監査ログの照会 | 通知の作成(POST) |
|---|---|---|---|
| フル権限キー | できる | できる | できる |
| 閲覧用キー | できる | できる | **できない(403)** |

閲覧用キーで通知を作成しようとすると、`401` ではなく `403` が返る:

```json
{
  "error": {
    "code": "INSUFFICIENT_SCOPE",
    "message": "このAPIキーには通知を作成する権限がありません"
  }
}
```

## 401 と 403 の違い(この課題の核)

| | 401 | 403 |
|---|---|---|
| 意味 | **認証**の失敗 — 誰か分からない | **認可**の失敗 — 誰かは分かるが、権限が足りない |
| サーバーから見て | キーが無い・無効 | キーは有効。ただしその操作の権限が無い |
| 対処 | キー・ヘッダーの側を直す | **権限の付与を依頼する**か、権限のあるキーに切り替える |
| リトライ | 直らない | 直らない(権限が変わらない限り) |

評価の順序は**認証 → 認可**。キー自体が無効なら、権限を調べる前に 401 で止まる
(403 が返ってきた時点で「認証は通っている」ことが確定する)。

## 手順のcurl例(PowerShell 7系 / Git Bash 共通)

手順2 — 閲覧用キーで既存の通知を参照(`ntf_000001` は自分の通知IDに置き換える):

```text
docker compose exec app curl -s http://pcp:8080/v1/notifications/ntf_000001 -H "Authorization: Bearer PCP_TEST_KEY_readonly00003333"
```

→ `200` で通知が返る = **このキーで認証は通っている**

(手元に通知IDが無い場合は、先にフル権限キーで1件作成する:)

```text
docker compose exec app curl -s -X POST http://pcp:8080/v1/notifications -H "Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718" -H "Content-Type: application/json" -d '{"recipient":"test-user-001","message":"C-003の題材"}'
```

手順3 — **同じ閲覧用キー**で通知を作成してみる:

```text
docker compose exec app curl -s -X POST http://pcp:8080/v1/notifications -H "Authorization: Bearer PCP_TEST_KEY_readonly00003333" -H "Content-Type: application/json" -d '{"recipient":"test-user-001","message":"閲覧用キーで作成できるかの確認"}'
```

→ `403` と `INSUFFICIENT_SCOPE` が返る

手順4 — 監査ログで403の記録だけを絞り込む:

```text
docker compose exec app curl -s -H "Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718" "http://pcp:8080/v1/audit-log?event_type=INSUFFICIENT_SCOPE"
```

## 監査ログでの見え方

- 参照の成功: `event_type: "SUCCESS"`・`api_key_suffix: "3333"`・`method: "GET"`
- 作成の拒否: `event_type: "INSUFFICIENT_SCOPE"`・`api_key_suffix: "3333"`・`http_status: 403`

**同じ末尾4桁のキーが、参照では成功し、作成では拒否されている**のがログで一目で分かる。
401(キー無しなら suffix は null)との違いが、記録の形にも表れている。

## 閲覧用キーが 401 になってしまう場合

PCP サーバーが古い(閲覧用キーの追加前のイメージのまま)可能性が高い。
`docker compose up -d --build` で `pcp` を再ビルドしてから試し直す。

## 画面での観察(任意)

admin の監査ログ画面(`/admin/pcp-audit-log.php`)で、同じキー末尾 `3333` の
参照の SUCCESS と作成の INSUFFICIENT_SCOPE が並ぶのを確認できる。
「認証は通り、権限で断られた」という切り分けが、記録の形でも見える。
