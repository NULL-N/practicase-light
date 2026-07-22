---
id: D-022
title: 設計基礎1: テーブルを考える
level: 1
track: design
type: design
priority: normal
estimated_minutes: 20
role: designer
dojo_foundation_stage: 1
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# D-022: 設計基礎1: テーブルを考える

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 成果物はテーブル洗い出しメモ
> `reports/D-022_tables_note.md` の1枚です。

早瀬さんから:

> 本格的な設計の課題に入る前に、まず小さいお題で肩慣らしをしましょう。
> NullWorksの社内向けに「メンバー管理」の簡単な仕組みを作るとしたら、
> どんなテーブルが要るか考えてみてください。給与・勤怠・評価・個人番号・住所・健康情報のような
> 機微な情報は扱いません。あくまで「誰がどの部署にいるか」が分かる程度の、社内アドレス帳に
> 毛が生えたくらいの小さな仕組みです。

これは**設計基礎編**の最初の課題です。この先の設計課題で必要になる「PK/FK/型/NOT NULL/
UNIQUE」といった判断を、組み合わせる前に1つずつ素振りします。この基礎編は8段階(D-022〜D-029)で、
最後のD-029でCREATE TABLE文一式を完成させます。**コードは一切書きません**。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`D-022`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-D-022-member-management-tables
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-D-022-member-management-tables`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. 「社内メンバー管理」に何のテーブルが要りそうか考える(1つではなく、複数に分けた方が
   良さそうな理由も考える)
2. `reports/D-022_tables_note.md` を作り、`support/spec.md` の型でテーブルを列挙する
3. それぞれのテーブルについて、「何のためのテーブルか」を一行で書く

## スコープ外(明記すること)

以下は今回のメンバー管理では**扱いません**。テーブルの中にこれらの情報を持たせないでください。

- 給与
- 勤怠
- 評価
- 個人番号(マイナンバー等)
- 住所
- 健康情報

## 完了条件

- `docker compose exec app php tools/check.php D-022` が PASS(テーブルが2つ以上、それぞれに一行の役割説明があること)
- スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていないこと

## 完了したら(次の一歩)

次は D-023(カラムを考える)— ここで洗い出したテーブルに、どんな列が要るかを考えます。

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
   `reports/D-022_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/D-022-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。
