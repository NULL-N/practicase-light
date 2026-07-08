# D-029 spec: DDLレビューメモの型

## 提出物の型(reports/D-029_ddl_review.md)

この型をそのまま使ってください。**`sql`コードブロックの中には、`CREATE TABLE`文だけを
書いてください**(この課題ではテーブルを作る文だけを検証します。`DROP`・`ALTER`・
`INSERT`等は書かないでください)。

````markdown
# D-029 DDLレビューメモ

## DDL

```sql
CREATE TABLE departments (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE members (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT '在籍中',
    department_id INTEGER NOT NULL,
    joined_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    left_at TEXT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
);
```

## 設計レビュー

- D-022(テーブル): departments・membersの2テーブルに分けた判断のまま
- D-023(カラム): 氏名・メール・状態・部署参照など、洗い出したカラムが揃っているか
- D-024(型): 文字列はTEXT、数値の参照はINTEGER、日時はTEXT(DATETIME相当)になっているか
- D-025(NULL/DEFAULT): 必須項目がNOT NULLか、状態にDEFAULTがあるか
- D-026(UNIQUE): メールにUNIQUE、氏名には付けていないか
- D-027(PK/FK): 両テーブルにPRIMARY KEY、部署参照にFOREIGN KEYがあるか
- D-028(ER図): ER図で描いた関係と、このDDLが一致しているか

## 気をつけたこと・見直したこと

(書いていて気づいたこと・直したこと・迷ったことを書く)
````

## 実行できるSQLの範囲

この課題で書けるのは`CREATE TABLE`文だけです。`DROP`・`ALTER`・`INSERT`・`PRAGMA`等が
混ざっていると、その時点でcheckが落ちます。テーブルの再設計をしたくなったら、
コードブロックの中身を書き直してください(前のテーブル定義を消す必要はありません
— 提出するのは最終的な状態だけです)。

## 決めなくてよいこと

- インデックスの細かいチューニング
- アプリケーションのコード(この章では一切書かない)

## 表記の自由

- カラムの型表記(`TEXT`/`VARCHAR`等)はSQLiteとして解釈できれば自由です
- `ON DELETE RESTRICT`を明示的に書かず省略しても構いません(SQLiteの既定動作が
  同等の挙動になります)
