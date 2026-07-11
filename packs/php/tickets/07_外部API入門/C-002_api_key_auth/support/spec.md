# PCP 認証仕様(C-002 で使う範囲)

C-001 の仕様書(エンドポイント全体)を前提に、この課題では**認証と401**だけを深掘りする。

- ベースURL: `http://pcp:8080`(`app` コンテナ内からのみ)
- 教材用テストキー: `PCP_TEST_KEY_a1b2c3d4e5f60718`

## 認証の仕組み

`/v1/health` 以外のエンドポイントは、リクエストヘッダーで API キーを渡す:

```text
Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718
```

- `Bearer` とキーの間は**半角スペース1つ**
- キーとして解釈できるものが無い(ヘッダーが無い・形式が崩れている)場合も、
  キーが登録されていない場合も、どちらも `401` と `INVALID_API_KEY` が返る

## 401 のレスポンス

```json
{
  "error": {
    "code": "INVALID_API_KEY",
    "message": "APIキーが無効です"
  }
}
```

**401 は「認証できない」= サーバーがあなたを「誰か分からない」と言っている状態。**
同じリクエストを何度送り直しても直らない(直すのはキー・ヘッダーの側)。

## 試す3パターン(curl 例・PowerShell 7系 / Git Bash 共通)

成功(対照・201):

```text
docker compose exec app curl -s -X POST http://pcp:8080/v1/notifications -H "Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718" -H "Content-Type: application/json" -d '{"recipient":"test-user-001","message":"認証の対照実験"}'
```

失敗1 — キー無し(`Authorization` ヘッダーの行ごと付けない):

```text
docker compose exec app curl -s -X POST http://pcp:8080/v1/notifications -H "Content-Type: application/json" -d '{"recipient":"test-user-001","message":"キー無しの実験"}'
```

失敗2 — 誤ったキー(形式は正しいが登録されていないダミー):

```text
docker compose exec app curl -s -X POST http://pcp:8080/v1/notifications -H "Authorization: Bearer PCP_TEST_KEY_0000000000000000" -H "Content-Type: application/json" -d '{"recipient":"test-user-001","message":"誤ったキーの実験"}'
```

## 監査ログでの見え方

失敗の記録だけに絞って確認できる(URL は引用符で囲む):

```text
docker compose exec app curl -s -H "Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718" "http://pcp:8080/v1/audit-log?event_type=INVALID_API_KEY"
```

- **キー無し**: `api_key_suffix` は `null`(キーとして解釈できるものが無かった)
- **誤ったキー**: `api_key_suffix` に**そのキーの末尾4桁**が残る(全文は残らない)

応答(401)では同じに見えた2つの失敗が、ログでは区別できる。
問い合わせを受けた側は「キーの付け忘れか・キーの取り違えか」を
suffix の有無で切り分けられる — これがこの記録設計の実務上の意味。

## 401 と 403 は別物(予告)

- `401` = 認証できない(誰か分からない)
- `403` = 認証はできたが、権限が足りない(誰かは分かっている)

この違いは後続の課題で実際に体験する。この課題では 401 だけを扱う。

## 画面での観察(任意)

admin の監査ログ画面(`/admin/pcp-audit-log.php`)を event_type = INVALID_API_KEY で
絞り込むと、この課題の2つの失敗が並ぶ。キー無し(キー末尾が空)と
誤ったキー(末尾4桁が残る)の記録の違いを、画面でも見比べてみる。
