# T-028 関連資料(答えではなく、参照先のポインタです)

- `packs/php/app/src/Service/AuthService.php`(現在の正しいコード。この PR の差分と見比べる)
- T-018 の報告書・仕様: `users.status = 'suspended'` はログイン不可、という仕様の根拠
  (`docs/01_設計資料/database.md` の `users` テーブル定義にも記載がある)
- T-019 の `support/spec.md`: 修正後も壊れてはいけない条件の一覧
  (admin ログイン成功・engineer/client の通常ログイン・パスワード不一致・存在しないメール・
  suspended の拒否)

**ヒント**: `if (A && B) { return null; }` という条件は、「A も B も両方成り立つときだけ
拒否する」という意味です。元の2つの独立した `if` 文(A なら拒否・B なら拒否)と、
本当に同じ意味になっているか、実際に手を動かして確認してみてください
(A=true, B=false のときの結果を比べるのがおすすめです)。
