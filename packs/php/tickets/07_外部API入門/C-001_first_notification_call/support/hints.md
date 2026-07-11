# C-001 ヒント(詰まったら上から順に)

## ヒント1: どこから叩くのか分からない

PCP はホスト(あなたのPC)からは見えない。すべてのコマンドを
`docker compose exec app ...` で始めて、`app` コンテナの中から実行する。
まずは認証不要の疎通確認から:

```text
docker compose exec app curl http://pcp:8080/v1/health
```

`{"status":"ok"}` が返れば PCP は動いている。返らなければ
`docker compose up -d` でコンテナを起動し直す。

## ヒント2: 401(INVALID_API_KEY)が返る

認証ヘッダーの形式を確認する。`Authorization: Bearer <キー>` の形で、
`Bearer` と キーの間は半角スペース1つ。キーは `support/spec.md` にある
教材用テストキーをそのまま使う(前後に余計な文字を付けない)。

## ヒント3: 400(INVALID_REQUEST)が返る

リクエストボディの JSON に `recipient` と `message` の両方があるか確認する。
シェルによっては引用符の扱いで JSON が壊れる — `support/spec.md` の curl 例のように、
**JSON 全体を外側のシングルクォートで囲む形**(`-d '{"recipient":...}'`)にすると、
PowerShell 7系でも Git Bash でもクォートが崩れにくい。JSON の中の二重引用符を
バックスラッシュでエスケープする書き方(`-d "{\"recipient\":...}"`)は使わない。

## ヒント4: status が queued のまま変わらない

状態は「参照した瞬間」に評価される。作成から約2秒たってから、
もう一度同じ `GET /v1/notifications/{id}` を実行する。
`fail-` で始まる宛先だけが `failed` になり、それ以外は `delivered` になる。

## ヒント5: 監査ログの見方

全部を眺めるより、絞り込みを使うと観察しやすい:

```text
docker compose exec app curl -s -H "Authorization: Bearer PCP_TEST_KEY_a1b2c3d4e5f60718" "http://pcp:8080/v1/audit-log?limit=10"
```

`api_key_suffix` の値に注目する。あなたが送ったキーの全文と見比べると、
何が記録されて何が記録されていないかが分かる。

## ヒント6: 報告書に何を書けばいいか

3つの見出し(ticket.md 参照)それぞれ2〜4行で十分。
「事実(何が返った・何が残った)」と「そこから読み取れる意図(なぜ末尾4桁だけなのか)」を
分けて書くと、実務の報告として通用する形になる。
