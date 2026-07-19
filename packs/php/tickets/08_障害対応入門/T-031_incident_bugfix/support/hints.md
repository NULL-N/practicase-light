# T-031 hints

## Hint 1: T-029 の自分の報告が地図になる

`reports/T-029.md` の `direct_cause` に書いたはずです — 欠陥は
`application_decide.php`(message の `called in ... on line 40`)にあり、
`at=` の `Flash.php:15` は「null を受け取って型エラーを出した場所」にすぎません。
開くべきファイルは `packs/php/app/public/client/application_decide.php` です。

## Hint 2: $result の3つの顔

該当箇所を読むと、`$result` は `match(...)` の結果です。成功なら `true`、
業務エラーなら文字列、そして**該当する応募が見つからなければ `null`**。
いまのコードには `true` の分岐と「それ以外(else)」しかありません。
null が else に流れ込むと何が起きるか — それが T-029 で見た TypeError です。

## Hint 3: このアプリの「該当なし」の型を探す

同じアプリの他の画面を見てください。たとえば `packs/php/app/public/applications/create.php` には
`abort404()` を呼ぶ防御があります。「該当するデータがなければ 404」は
このアプリ共通の型(設計ルール D-1 / G-4)です。同じ型をこの画面に復元します。

## Hint 4: 復元する形と位置

```php
if ($result === null) {
    abort404(); // D-1 / G-4
}
```

置く位置は `$result = match (...) { ... };` の**直後**です。
`if ($result === true)` より後ろに置くと、null は先に `Flash::error()` へ
たどり着いてしまいます(防御は「到達する前」に置く)。

## Hint 5: 直したら、壊していないことを確かめる

check の PASS に加えて、`support/spec.md` の回帰確認を実際にやってみてください。
「存在しない ID が 404」「正常な承認は 302」「新しい例外ログが増えない」——
3つ揃って、志村さんに『直りました』と言えます。
