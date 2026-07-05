# T-018 hints

## Hint 1: まず AuthService.php を開く

`packs/php/app/src/Service/AuthService.php` の `attempt()` メソッドを開いてください。
中身はだいたいこういう形になっています(実際のコードで確認してください)。

```php
if ($user === null) {
    return null; // 該当ユーザーなし
}
if ($user['status'] !== 'active') {
    return null; // 停止中もログイン不可
}
if (!password_verify($password, $user['password_hash'])) {
    return null; // パスワード不一致
}
```

3つとも同じ `null` を返しています。呼び出す側(画面)は、この3つを区別できません。
だから画面には毎回同じエラー文言が出るのです。

## Hint 2: 3つの原因のうち、どれか

田淵さんは「パスワードは変えていない」と言っています。だとすると、疑わしいのは
1番目(該当ユーザーなし)か、2番目(停止中)です。1番目は考えにくい
(昨日まで使えていた=アカウント自体はある)ので、2番目を疑ってみましょう。

## Hint 3: SQLで確認する

```sql
SELECT id, email, role, status FROM users WHERE email = 'tabuchi@example.com';
```

これで田淵さんの `status` が分かります。`suspended` になっていれば、Hint 2 の予想が
当たりです。

## Hint 4: 報告書の書き方に迷ったら

`docs/templates/inquiry_investigation_report.md` を開くと、6つの見出しが
すでに用意されています。上から埋めていけば大丈夫です。「対応方針」には、
自分では実行していないことも正直に書いてください(この課題はコードもデータも
変更しません)。
