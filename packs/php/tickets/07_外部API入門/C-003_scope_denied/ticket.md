---
id: C-003
title: PCPの権限スコープを試し、403を切り分ける
level: 2
track: cloud
type: cloud
priority: normal
estimated_minutes: 45
role: developer
status: open
scope:
depends_on:
  - "C-002"
pack: php
---

# C-003 PCPの権限スコープを試し、403を切り分ける

## 背景

連携先チーム(PCP運用側)から、2本目のキーが共有された。

> 監視・集計ツール用に**閲覧用のキー**を発行しました:
> `PCP_TEST_KEY_readonly00003333`
> このキーは通知の**参照はできますが、作成はできません**。
> ツールに組み込む前に、「参照はできて、作成はできない」ことを
> 実際に確かめておいてもらえますか。作成できてしまったら発行ミスなので教えてください。

C-002 で見た 401 は「**誰か分からない**」だった。今回は認証は通る —
つまりサーバーは**誰かは分かっている**。それでも断られるのが **403** で、
401 とは原因も対処もまったく違う。この切り分けが今回の本題。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`C-003`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-C-003-scope-denied
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-C-003-scope-denied`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## この課題でやること

1. **PCPサーバーを最新にする**: `docker compose up -d --build`
   (閲覧用キーは最近追加されたもの。再ビルドしないと古いサーバーには存在しない)
2. `support/spec.md`(スコープと403)を読み、**閲覧用キーで既存の通知を1件 GET する**
   — `200` が返ること(= このキーで**認証は通っている**)を自分の目で確かめる。
   手元に通知IDが無ければ、フル権限キーで1件作ってから試す
3. **閲覧用キーで通知を作成(POST)してみる** — 何が返るかを観察する
   (401 ではないことに注目)
4. `GET /v1/audit-log?event_type=INSUFFICIENT_SCOPE` で 403 の記録を確認する —
   同じ末尾4桁(`3333`)のキーが、参照では `SUCCESS`・作成では
   `INSUFFICIENT_SCOPE` として並んでいるはず
5. 報告書を `reports/C-003_report.md` に書く

## 報告書の形式(必須)

次の3つの見出しをこの文言のまま使うこと:

```text
## 何を試したか
## 403から何が分かったか
## 401と403をどう切り分けるか
```

- 「何を試したか」は、**同じキーで参照(200)と作成(403)を両方**試したことが
  分かるように書く
- 「403から何が分かったか」には、返ってきたエラーコード(`INSUFFICIENT_SCOPE`)と、
  403 が**どういう状態**なのかを自分の言葉で書く
- 「401と403をどう切り分けるか」には、**原因の違い**と**対処の違い**を書く
  (403 のとき、リトライやキーの綴り確認をしても意味があるか?)

## 注意(安全)

- 使ってよいのは教材用テストキー(`PCP_TEST_KEY_` で始まるダミー)だけ。
  **実在のサービスの API キーやパスワードを、コマンドにも報告書にも絶対に書かない**

## 完了の定義

- [ ] 閲覧用キーで参照(200)と作成(403)の両方を確認した
- [ ] 監査ログで `INSUFFICIENT_SCOPE` の記録を確認した
- [ ] 401(認証)と403(認可)の違いを報告書で言語化した
- [ ] `reports/C-003_report.md` を3つの見出しで書いた
- [ ] `docker compose exec app php tools/check.php C-003` が PASS

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
   `reports/C-003_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/C-003-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。
