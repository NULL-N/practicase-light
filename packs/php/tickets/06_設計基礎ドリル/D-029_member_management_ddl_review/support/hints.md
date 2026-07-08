# D-029 ヒント(段階的に。詰まった分だけ読む)

## Hint 1: D-022〜D-028を順に見返しながら書く

いきなりDDLを書こうとせず、`reports/D-022_tables_note.md`から
`reports/D-028_er_diagram.md`まで、順番に見返しながら1カラムずつDDLに落として
いってください。特にD-024(型)・D-025(NULL/DEFAULT)・D-026(UNIQUE)・D-027(PK/FK)の
4段階が、そのままDDLの1行1行に対応します。

## Hint 2: SQLiteでの書き方に迷ったら

- 主キー: `id INTEGER PRIMARY KEY`
- 必須(NOT NULL): `name TEXT NOT NULL`
- 重複禁止(UNIQUE): `email TEXT NOT NULL UNIQUE`
- 初期値(DEFAULT): `status TEXT NOT NULL DEFAULT '在籍中'`
- 外部キー: テーブル定義の最後に
  `FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT`

## Hint 3: 実行エラーになったら

よくあるミスは、カンマの過不足と、括弧の閉じ忘れです。`CREATE TABLE`の各行は
最後の行以外すべてカンマで終わり、最後の行(または`FOREIGN KEY`行)にはカンマを
付けません。1つずつのテーブル定義が、開き括弧`(`と閉じ括弧`)`のペアで
ちゃんと閉じているか確認してください。

## Hint 4: 設計レビュー欄の書き方に迷ったら

「D-024で文字列型にしたname・emailが、DDLでもTEXTになっているか」のように、
各段階の判断とDDLの該当箇所を1対1で見比べる書き方をすると、抜けや矛盾に
気づきやすくなります。全部「問題なし」で終わらせず、実際に見比べた跡が
残っていると良いです。
