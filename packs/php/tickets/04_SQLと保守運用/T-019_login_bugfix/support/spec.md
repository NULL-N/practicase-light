# T-019 仕様メモ

参照するコード:

- `packs/php/app/src/Service/AuthService.php`(`attempt()`)

必要な条件:

- `role` が `admin` のユーザーが、正しいメールアドレス・パスワードでログインできる
  (`attempt()` が `null` ではなくユーザー情報を返す)
- 以下は修正後も変わらず正しく動くこと:
  - `engineer` / `client` を含む、正しい資格情報でのログイン
  - パスワード不一致(`attempt()` は `null`)
  - 存在しないメールアドレス(`attempt()` は `null`)
  - `status = 'suspended'` のユーザー(`attempt()` は `null`。T-018 で確認した仕様)
- 修正は `AuthService.php` の中だけで完結する(他のファイルは変更しない)
