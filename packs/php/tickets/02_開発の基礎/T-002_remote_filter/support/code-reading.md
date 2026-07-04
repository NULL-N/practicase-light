# T-002 コードリーディング(検索 SQL を組み立てるコードの読み方)

`ProjectRepository` の `search()` を読むための補助資料です。
**バグの答えは書いていません。** 正しく書けている「手本」の部分を1行ずつ解説するので、
これを読み方の型にして、足りないものを自分で読み解いてください。
(コード全体の地図は `docs/03_参考資料/code-tour.md`。この資料はその一段細かい「1メソッドの精読」です)

## 前提: search() は何をするメソッドか

検索条件に応じて、SQL 文を**その場で組み立てて**実行するメソッドです。
T-001 の validate() が「入力を1項目ずつ検証する」だったのに対し、
search() は「条件を1つずつ WHERE に積む」— 部品は違っても「1つずつ」の読み方は同じです。

- `$where` … WHERE 句のパーツ(SQL 片)を溜めていく配列
- `$params` … プレースホルダ(`:today` 等)に後から入れる実際の値(SEC-1: 値を SQL 文字列に直接埋め込まない)

最後に `implode(' AND ', $where)` が、溜まったパーツを ` AND ` で連結して1本の WHERE 句にします。
つまり — **積まれなかった条件は、最終的な SQL に存在しません。**

## 入口: 画面からどう届くか

`public/projects/index.php`(画面)が入力を受け取り、Repository へ引数で渡します。

| コード | 何をしているか |
|---|---|
| `$_GET['remote_only'] ?? ''` | チェックボックスは ON のとき `'1'` が届く。OFF はキー自体が無い(`??` で空文字に) |
| `=== '1'` | 厳密比較で true / false に変換。以降 `$remoteOnly` は bool |
| `$repository->searchOpen($keyword, $remoteOnly, ...)` | 検索条件が**引数のバケツリレー**で Repository へ渡る |

searchOpen(engineer 用)と searchAll(admin 用)は、どちらも private の `search()` に集約されます。
公開の入口は2つ、実体は1つ — この形なら、直すとき1箇所で済みます。

## 手本を読む: 母集合の絞り込み(onlyOpenSince)

```php
if ($onlyOpenSince !== null) {
    $where[] = "p.status = 'open' AND p.deadline >= :today";
    $params['today'] = $onlyOpenSince;
}
```

1行ずつ:

| コード | 何をしているか |
|---|---|
| `if ($onlyOpenSince !== null)` | この条件が「必要なときだけ」ブロックに入る。admin(全件)のときは null なので積まれない |
| `$where[] =` | 配列の末尾に SQL 片を1つ追加する書き方(`[]` は「押し込む」) |
| `p.deadline >= :today` | `:today` はプレースホルダ。**値そのものはここに書かない** |
| `$params['today'] = ...` | プレースホルダに対応する実際の値を、別の配列に**対で**入れる |

## 手本を読む2: キーワード検索

```php
if ($keyword !== '') {
    $where[] = "(p.title LIKE :kw ESCAPE '\\' OR p.description LIKE :kw2 ESCAPE '\\')";
    $like = '%' . self::escapeLike($keyword) . '%';
    $params['kw'] = $like;
    $params['kw2'] = $like;
}
```

| コード | 何をしているか |
|---|---|
| `if ($keyword !== '')` | 空なら条件を積まない = 「空なら条件なし」(F-03)の実装 |
| `LIKE :kw ... OR ... :kw2` | title **または** description に部分一致(F-03 の表のとおり) |
| `self::escapeLike(...)` | `%` や `_` を文字として扱うためのエスケープ(F-03)。道具は同じクラスの下部に定義されている |
| `'%' . ... . '%'` | 前後に `%` を付けて「どこかに含む」の形にしてから `$params` へ |

## 読み取れる型

「**①条件が必要なときだけ(if) ② `$where[]` に SQL 片を積み ③ 値は `$params` に対で入れる**」。
どの検索条件も、この3点セットが1ブロックです。そして組み立ての最後:

```php
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
```

`$where` が空なら WHERE 句ごと付かない(全件)。積んだ数だけ AND で並ぶ。
**この方式では、条件の「存在」は if ブロックの「存在」と同じ意味**です。

## ここから自分で

`docs/01_設計資料/features.md` F-03 の「検索条件」の表を開いてください。

- 表にある条件は**いくつ**? `search()` の中で `$where` に積んでいる if ブロックは**いくつ**?
- エディタで `$remoteOnly` を検索(Ctrl+F)してください。**引数の宣言以外**で、どこに登場しますか?

答えは書きません。仕様の表と、目の前のコード — この2つを並べて数えれば、
何が足りないかは自分の目で見えます。それを見つけるのが T-002 です。
