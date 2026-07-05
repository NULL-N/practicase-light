# T-016 hints

## Hint 1

応募が0件の案件も出す必要があります。
普通の `JOIN` だと、応募が無い案件は消えます。

## Hint 2

この課題では `LEFT JOIN` を使います。

```sql
FROM projects p
LEFT JOIN applications a ON a.project_id = p.id
```

## Hint 3

承認済みだけを数えるには、条件付きで 1 / 0 を足し上げます。

```sql
SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END)
```
