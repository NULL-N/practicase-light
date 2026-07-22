---
id: D-026
title: 設計基礎5: UNIQUEを決める
level: 1
track: design
type: design
priority: normal
estimated_minutes: 20
role: designer
dojo_foundation_stage: 5
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# D-026: 設計基礎5: UNIQUEを決める

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 成果物はUNIQUE判断メモ
> `reports/D-026_unique_note.md` の1枚です。

早瀬さんから:

> D-025のNULL判断、良かったです。次は、**「同じ値が複数の行にあってよいか」**を
> 考えてみてください。主キーや他のテーブルとのつながりの話はまだ早いです。今回は
> 純粋に「業務として、この値は重複してはいけないか」だけを判断してください。

これは**設計基礎編**の5段階目です。D-025までの判断を、そのまま引き継いで使ってください。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`D-026`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-D-026-member-management-unique
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-D-026-member-management-unique`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. `reports/D-026_unique_note.md` を作り、カラムごとに「UNIQUEあり / UNIQUEなし」と、
   なぜそう判断したかを書く
2. メールアドレスは、UNIQUEにしてください(ログインや連絡先として、同じメールアドレスの
   メンバーが複数いると本人特定ができなくなります)
3. 部署名は、UNIQUEにするかしないか、**自分で判断してください**。どちらでも構いません。
   ただし理由が必要です
   - UNIQUEにするなら:「同じ名前の部署が複数あると混乱するから」のような理由
   - UNIQUEにしないなら:「組織変更で一時的に同名部署が併存しうるから」のような理由
4. 氏名は、UNIQUEに**しないで**ください。世の中には同姓同名の人が普通にいます
5. 状態(在籍状況等)は、UNIQUEに**しないで**ください。複数のメンバーが同じ状態
   (例: 「在籍中」)であることは当然です
6. **すべてのカラムを機械的にUNIQUEにする、ということはしないでください。**
   「重複してはいけない理由が具体的に言えるか」で判断してください

## スコープ外(明記すること)

D-022〜D-025と同じく、以下は扱いません。

- 給与
- 勤怠
- 評価
- 個人番号(マイナンバー等)
- 住所
- 健康情報

## 完了条件

- `docker compose exec app php tools/check.php D-026` が PASS
- メールアドレスがUNIQUEになっている
- 部署名についてUNIQUEの有無どちらかが明示され、理由が書かれている
- 氏名・状態がUNIQUEにされていない
- 全カラムを機械的にUNIQUEにしていない
- 主キー・外部キーの話が主題になっていない(それは D-027 の仕事)

## 完了したら(次の一歩)

次は D-027(PK/FKを設計する)— 主キーと、テーブル同士のつながりを設計します。

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
   `reports/D-026_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/D-026-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。
