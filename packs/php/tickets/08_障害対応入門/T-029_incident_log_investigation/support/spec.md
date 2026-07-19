# T-029 仕様メモ(ログの読み方と報告の記入仕様)

## ログの書式

1行 = 1イベント。grep で絞り込める形になっている:

```text
日時 [レベル] イベント名 request_id=... method=... path=... user=... (イベント固有の項目...)
```

全イベントに共通で付く項目:

| 項目 | 意味 |
|---|---|
| `request_id` | HTTP リクエスト単位の相関キー(16桁の英数)。**同じリクエスト内のログは同じ値**になる。レスポンスヘッダー `X-Request-ID` にも同じ値が付く |
| `method` | HTTP メソッド(GET / POST) |
| `path` | アクセスされた URL のパス(クエリ文字列は記録しない) |
| `user` | 操作したユーザーの ID(未ログインは `anonymous`) |

`[error]` の `uncaught_exception`(処理されなかった例外)には、さらに次が付く:

| 項目 | 意味 |
|---|---|
| `type` | 例外クラス名(例: `TypeError`) |
| `message` | 例外メッセージ。**`called in ファイル on line 行` が含まれることがある — 呼び出し元の情報**(重要。hints 参照) |
| `at` | **例外が発生した場所**(`ファイルパス:行番号`) |

## よく使う絞り込みコマンド

```text
docker compose exec app grep "\[error\]" "packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt"
docker compose exec app grep -c "\[error\]" "packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt"
docker compose exec app grep "request_id=ここに値" "packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt"
```

## 報告の契約ブロック: 各項目の定義

事実7項目は、固定ログの **`[error]` 行(今回の障害)** から次のとおり読み取る:

| キー | 定義 | 型 |
|---|---|---|
| `occurred_at` | 対象エラーのうち**最初(いちばん古い)行の日時**をそのまま(例: `2026-01-02 10:00:00` の形) | 文字列 |
| `path` | 対象エラー行の `path=` の値 | 文字列 |
| `user` | 対象エラー行の `user=` の値 | 文字列 |
| `event` | 対象エラー行のイベント名(`[error]` の直後の語) | 文字列 |
| `source_file` | 対象エラー行の `at=` の値から、**`packs/` 以降のファイルパス**(行番号の `:` より前) | 文字列 |
| `source_line` | 同じ `at=` の**行番号**(`:` の後の数字) | 整数 |
| `impact_count` | **同じ障害の error 行の件数** | 整数 |

自由文2項目:

| キー | 書くこと |
|---|---|
| `direct_cause` | 何が直接の原因でこの例外になったか。**`at` の場所と `message` の `called in` の場所の関係**まで読めていると強い(hints 参照) |
| `remediation_plan` | どう直すべきかの方針(自分では直さない)。修正後に確認すべきこと(回帰確認)まで書けるとよい |

## 注意

- 判定は固定ログに対してのみ行う。live ログ(`packs/php/app/logs/app.log`)は判定に使わない
- コードは変更しない(scope は `reports/T-029.md` のみ)
