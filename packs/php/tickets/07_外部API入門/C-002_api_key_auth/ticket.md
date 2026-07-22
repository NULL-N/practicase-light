---
id: C-002
title: PCPのAPIキー認証を試し、401を切り分ける
level: 2
track: cloud
type: cloud
priority: normal
estimated_minutes: 45
role: developer
status: open
scope:
depends_on:
  - "C-001"
pack: php
---

# C-002 PCPのAPIキー認証を試し、401を切り分ける

## 背景

C-001 で PCP の通知 API を呼び出せるようになった。本格的な組み込みを進める前に、
連携先チーム(PCP運用側)からこう頼まれている。

> 組み込みの前に、認証まわりの失敗が「どう見えるか」を把握しておいてください。
> キーを付け忘れたとき・間違ったキーを使ったときに何が返るのか、
> そのとき PCP 側の記録には何が残るのか。切り分けて報告してもらえると、
> 実際に問い合わせが来たときにお互いの調査が速くなります。

実務では、認証エラー(401)は「たまに動かない」という曖昧な問い合わせに化けやすい。
**わざと失敗させて、失敗の形を先に知っておく**のがこの課題。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`C-002`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-C-002-api-key-auth
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-C-002-api-key-auth`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## この課題でやること

1. `support/spec.md`(認証仕様と401)を読む
2. **対照実験の成功側**: 正しい教材用テストキーで通知を1件作成する(`201` を確認)
3. **失敗1(キー無し)**: `Authorization` ヘッダーを付けずに同じリクエストを送り、
   何が返るかを観察する
4. **失敗2(誤ったキー)**: 形式は正しいが登録されていないダミーキー
   `PCP_TEST_KEY_0000000000000000` で送り、同じく観察する
5. `GET /v1/audit-log?event_type=INVALID_API_KEY` で失敗の記録を確認する —
   **2つの失敗で `api_key_suffix` の値がどう違うか**に注目する
6. 報告書を `reports/C-002_report.md` に書く

## 報告書の形式(必須)

次の3つの見出しをこの文言のまま使うこと:

```text
## 何を試したか
## 401から何が分かったか
## 監査ログに何が残ったか
```

- 「何を試したか」は、成功1・失敗2の**対照実験**であることが分かるように書く
- 「401から何が分かったか」には、返ってきたエラーコード(`INVALID_API_KEY`)と、
  401 が**どういう状態**なのか(ヒント: サーバーはあなたを「誰」だと思っているか)を
  自分の言葉で書く
- 「監査ログに何が残ったか」には、キー無しと誤ったキーで**記録の残り方が
  どう違ったか**を書く

## 注意(安全)

- 使ってよいのは教材用テストキー(`PCP_TEST_KEY_` で始まるダミー)だけ。
  **実在のサービスの API キーやパスワードを、コマンドにも報告書にも絶対に書かない**
- 「誤ったキー」も `PCP_TEST_KEY_0000000000000000` のような明らかなダミーを使う
  (それらしい文字列を自作しない)

## 完了の定義

- [ ] 正しいキーでの成功(201)を確認した
- [ ] キー無し・誤ったキーの両方で 401 を確認した
- [ ] 監査ログで2つの失敗の記録の違いを見比べた
- [ ] `reports/C-002_report.md` を3つの見出しで書いた
- [ ] `docker compose exec app php tools/check.php C-002` が PASS

## 提出と完了(共通手順)

この節は、上の課題固有手順を提出までつなぐ補足です。手順1は作業前、手順2以降はcheckがPASSした後に行います。

1. Redmineでこのチケットを開き、担当者を自分にして、見積をコメントし、ステータスを
   `New` → `In Progress`にする(本文ですでに実施している場合は繰り返さない)
2. commit・pushする前に`support/rubric.md`の「提出前」を確認する。満たしていない項目があれば修正し、checkをやり直す
3. 変更をcommit・pushし、Pull Requestを作る。この時点ではまだmergeしない
4. Pull Requestをmergeする
5. `support/debrief/`がある課題は、Pull Requestをmergeした後に開いて自分の提出と突き合わせる。
   突き合わせで見つけた違いは振り返りに記録し、必要な修正は別チケットで扱う
   その結果を振り返りに書く。本文でファイル名の指定がなければ
   `reports/C-002_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/C-002-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。
