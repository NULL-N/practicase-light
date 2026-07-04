# T-014 hints

## Hint 1

まずは形だけ作ります。

```sql
SELECT ...
FROM projects
WHERE ...
ORDER BY ...;
```

## Hint 2

募集中の案件は `status = 'open'` です。
完了済みの案件は `status = 'closed'` なので、ここに混ぜないでください。

## Hint 3

締切が近い順は、`deadline` の昇順です。
昇順は `ASC` と書きます。
