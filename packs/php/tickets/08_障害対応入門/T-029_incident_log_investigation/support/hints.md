# T-029 hints

## Hint 1: まず error 行だけに絞る

20行のログを目で追うより、レベルで絞るのが最初の一手です。

```text
docker compose exec app grep "\[error\]" "packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt"
```

`[info]` は正常な操作の記録(ノイズ)。`[error]` の行が今回の障害です。
何行出てきましたか? それがそのまま `impact_count` になります(`grep -c` で数えられます)。

## Hint 2: 「いつ・どこで・誰が」を error 行から読む

error 行の先頭が日時です。複数あるなら**いちばん古い行**の日時が `occurred_at`。
同じ行の `path=` がどの画面で起きたか、`user=` が誰に起きたか。
3行の `path` と `user` が同じで、`request_id` だけ違うことに気づきましたか?
**同じ人が同じ操作を3回試して、3回とも失敗した**——問い合わせの「何度か試した」と一致します。

## Hint 3: at= が「例外の発生場所」

`at=/var/www/practicase/packs/php/app/src/Support/Flash.php:15` のように読めます。
`:` の前がファイル、後ろが行番号です。報告の `source_file` には **`packs/` から始まる部分**を、
`source_line` にはこの行番号を書きます。

## Hint 4: message の「called in」を見落とさない(この課題の核心)

`at` は Flash.php を指していますが、Flash.php が壊れているのでしょうか?
message をよく読むと:

```text
Flash::error(): Argument #1 ($message) must be of type string, null given,
called in .../public/client/application_decide.php on line 40
```

「**string であるべき引数に null が渡された。呼び出したのは application_decide.php の40行目**」
と書いてあります。例外が**発生した場所**(at)と、null を**渡した場所**(called in)は別です。
真の欠陥はどちら側にあるか — `direct_cause` にはこの理解を自分の言葉で書いてください。

## Hint 5: 修正方針の書き方

この課題では直しません。でも「どう直すべきか」までが調査報告の仕事です。
「呼び出し側で、結果が null(該当データなし)の場合の分岐を設けて 404 を返す」のような
最小限の方針と、修正後に何を確認するか(同じ操作が404になること・正常な承認が壊れていないこと)
を `remediation_plan` に書きます。

## Hint 6: 契約ブロックの型に注意

`source_line` と `impact_count` は**引用符なしの数字**、それ以外は `"..."` の文字列です。
check の FAIL メッセージは「どのキーが何と一致しないか」まで教えてくれるので、
1つずつ直せば必ず通ります。
