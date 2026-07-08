# D-029 突き合わせ(模範との比較)

自分の `reports/D-029_ddl_review.md` と見比べてください。**カラムの型表記・DDLの
書式が違っても構いません** — 「D-022〜D-028の判断が漏れなく反映されているか」が
この課題の核心です。

## 模範の内容(1つの妥当な例)

````markdown
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
````

## 別解について

- `left_at`(退職日、D-025の任意カラム)を含めるかどうかは自由です
- `ON DELETE RESTRICT`を明示的に書かず省略しても、SQLiteの既定動作(NO ACTION)が
  同等に働くため問題ありません
- 型の具体的な表記(`TEXT`か`VARCHAR`か等)はSQLiteとして解釈できれば自由です

## 見つかりやすい抜け

- **UNIQUEやFOREIGN KEYの書き忘れ**: `CREATE TABLE`が構文として実行できてしまうため、
  一見問題なく見えてしまいます。D-026・D-027の判断が実際にDDLへ反映されているか、
  1行ずつ見比べてください
- **氏名にPRIMARY KEYやUNIQUEを付けてしまう**: 基礎編を通して最も繰り返し
  強調してきた判断です。ここで崩れていないか、最後にもう一度確認してください
- **設計レビュー欄が「問題なし」の一言で終わっている**: 実際にD-022〜D-028を
  開いて見比べた跡が残る書き方(具体的な段階名・具体的なカラム名への言及)を
  してください

## この課題で本当に鍛えたいこと

「動くSQLが書ける」ことと、「積み上げてきた設計判断を、書いたコードが裏切っていないか
自分で確認できる」ことは、別の力です。実務でも、意図した設計とコードがずれていく
ことはよくあります。書いた後に自分で見直す習慣が、この基礎編全体の到達点です。
