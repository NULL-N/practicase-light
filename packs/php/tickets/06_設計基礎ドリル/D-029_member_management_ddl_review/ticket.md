---
id: D-029
title: 設計基礎8: DDLを書き、レビューする(基礎編卒業課題)
level: 2
track: design
type: design-review
priority: normal
estimated_minutes: 40
role: designer
dojo_foundation_stage: 8
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# D-029: 設計基礎8: DDLを書き、レビューする(基礎編卒業課題)

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません(SQLのDDLのみ)。** 成果物はDDLレビューメモ
> `reports/D-029_ddl_review.md` の1枚です。

早瀬さんから:

> ここまでD-022〜D-028、お疲れさまでした。最後は、これまで8段階かけて積み上げてきた
> 判断を、実際に動く`CREATE TABLE`文として書き切ってください。そして、書いた後に
> **自分で読み返して**、8段階の判断がちゃんと反映されているか、矛盾が無いかを
> レビューしてください。「SQLが書けた」で終わらせず、「設計として破綻していないか」
> まで見るのが、この基礎編の卒業条件です。

これは**設計基礎編の卒業課題**です。D-022〜D-028のすべての判断を、ここで1つの
DDLにまとめます。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`D-029`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-D-029-member-management-ddl-review
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-D-029-member-management-ddl-review`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. `reports/D-029_ddl_review.md` を作り、\`\`\`sql コードブロックに`CREATE TABLE`文を書く
   - 部署テーブル・メンバーテーブルの両方
   - 両テーブルに`id`の主キー(D-027)
   - 部署名・氏名・メール・状態・部署への参照カラム(D-023・D-024)
   - 部署名・氏名・メール・状態・部署参照はNOT NULL(D-025)
   - 状態にはDEFAULT(在籍中相当。D-025)
   - メールにはUNIQUE(D-026)。氏名にはUNIQUEを付けない(D-026)
   - 部署への参照は外部キー(D-027)。`ON DELETE RESTRICT`(または何も書かない=SQLiteの
     既定動作でも構いません)で、所属メンバーがいる部署を削除できないようにする
2. DDLを書いたら、**設計レビュー欄**を作り、D-022〜D-028の8段階それぞれの判断が
   このDDLに反映されているか、1つずつ確認して書く
3. **「気をつけたこと」または「見直したこと」欄**を作り、書いていて気づいたこと・
   直したこと・迷ったことを書く

## スコープ外(明記すること)

D-022〜D-028と同じく、以下は扱いません。

- 給与
- 勤怠
- 評価
- 個人番号(マイナンバー等)
- 住所
- 健康情報

## 完了条件

- `docker compose exec app php tools/check.php D-029` が PASS
- `CREATE TABLE`文が2つ以上あり、実際にSQLiteで実行できる(構文エラーが無い)
- 部署名・氏名・メール・状態・部署参照の各カラムが、これまでの判断どおりの
  制約(NOT NULL・UNIQUE・DEFAULT・FK)になっている
- 氏名がUNIQUEにもPRIMARY KEYにもなっていない
- FKの削除時挙動がRESTRICT相当になっている
- 設計レビュー欄・気をつけたこと(見直したこと)欄がある

## この課題の位置づけ

「SQLが完璧に美しいか」より、**D-022〜D-028で積み上げてきた判断がDDLに正しく
反映されているか**が大事です。書き終えたら、この課題の一つ前の段階(D-028のER図)まで
遡って見比べてみてください。

## 完了したら

設計基礎編(D-022〜D-029)は、これで完結です。お疲れさまでした。

> 詰まったら `support/hints.md`。段階的に4つ用意しています。

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
   `reports/D-029_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/D-029-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。
