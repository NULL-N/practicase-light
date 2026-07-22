---
id: D-023
title: 設計基礎2: カラムを考える
level: 1
track: design
type: design
priority: normal
estimated_minutes: 20
role: designer
dojo_foundation_stage: 2
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# D-023: 設計基礎2: カラムを考える

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 成果物はカラム洗い出しメモ
> `reports/D-023_columns_note.md` の1枚です。

早瀬さんから:

> D-022で洗い出してもらったテーブル、いいと思います。次は、それぞれのテーブルに
> **どんな情報(カラム)を持たせるか**を考えてみてください。まだ型やNULL・重複禁止・
> キーのようなことは決めなくて大丈夫です。「このテーブルの1行には、どんな情報が
> 並んでいるべきか」だけを考えてください。

これは**設計基礎編**の2段階目です。D-022で決めたテーブル(部署にあたるテーブル・
メンバーにあたるテーブル)を、そのまま引き継いで使ってください。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`D-023`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-D-023-member-management-columns
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-D-023-member-management-columns`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. D-022の `reports/D-022_tables_note.md` を見返し、自分が決めたテーブル名を確認する
2. `reports/D-023_columns_note.md` を作り、`support/spec.md` の型でテーブルごとに
   カラム候補を箇条書きで挙げる(1テーブルにつき2つ以上)
3. 部署にあたるテーブルには、部署の名前が分かるカラムを入れる
4. メンバーにあたるテーブルには、氏名・メールアドレス・在籍状態(在籍中か退職済みか等)・
   どの部署に属しているかが分かるカラムを入れる

## スコープ外(明記すること)

D-022と同じく、以下は扱いません。

- 給与
- 勤怠
- 評価
- 個人番号(マイナンバー等)
- 住所
- 健康情報

## 完了条件

- `docker compose exec app php tools/check.php D-023` が PASS
- テーブルごとにカラム候補が2つ以上挙がっている
- 部署テーブルに部署名にあたるカラムがある
- メンバーテーブルに氏名・メール・状態・部署参照にあたるカラムがある
- 型・NULL・UNIQUE・PK/FKのような細かい制約には踏み込みすぎていない
  (それは D-024 以降の仕事です)

## 完了したら(次の一歩)

次は D-024(型を選ぶ)— ここで挙げたカラムそれぞれに、ふさわしいデータ型を考えます。

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
   `reports/D-023_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/D-023-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。
