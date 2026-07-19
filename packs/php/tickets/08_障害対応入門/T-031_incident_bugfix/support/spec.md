# T-031 仕様メモ(復元する防御と回帰確認)

## この画面の仕様(D-1 / G-4)

`/client/application_decide.php` は応募の承認/却下を実行する画面。処理結果 `$result` は
3種類の値を取る:

| `$result` | 意味 | あるべき応答 |
|---|---|---|
| `true` | 承認/却下が成功 | 完了メッセージを出して一覧へ戻る(302) |
| 文字列 | 業務上のエラーメッセージ(例: 定員超過) | メッセージを表示して一覧へ戻る(302) |
| `null` | **該当する応募が見つからない**(存在しない ID・他社の応募・不正な操作値) | **404 応答**(`abort404()`) |

障害の状態では null の分岐が欠けており、null が `Flash::error(string)` に渡って
TypeError(500)になっていた — これが T-029 で確定した事実。

## 復元する防御の仕様(check が確認する内容)

- `null` との**厳密比較**(`===`)で判定し、`abort404()` を呼ぶ
- 位置: `match(...)` で `$result` を受けた**後**、成功判定 `if ($result === true)` と
  `Flash::error(...)` に到達する**前**
- 次は不合格になる:
  - `false` との比較(「該当なし」は null。false と null は別物)
  - `empty()` 判定(文字列のエラーメッセージまで巻き込む)
  - `throw` での代替(この画面の仕様は 404 応答。500 のままにしない)
  - コメントや文章として書くだけ(実コードとして判定される)
- `Flash.php` / Service / Repository は変更しない(scope 検査が機械的に検出する)

## 回帰確認の手順(修正後に自分の目で)

1. client(志村さん `shimura@example.com` / `password123`)でログインし、応募一覧を開く
2. ブラウザの開発者ツール(F12)で承認フォームの `application_id` の value を
   `99999` に書き換えて送信 → **404 ページ**になること(修正前は 500)
3. live ログ(`packs/php/app/logs/app.log`)に新しい `uncaught_exception` が
   **増えていない**こと(`docker compose exec app tail packs/php/app/logs/app.log`)
4. 通常の承認/却下がこれまでどおり成功すること(302 で一覧に戻り、完了メッセージ)
