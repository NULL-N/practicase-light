# LEARN_HERE — 学習者用の入口

PractiCase for PHP のフォルダは、実務に近づけるため少し複雑です。
ただし、最初から全部を見る必要はありません。

まずは、この順番だけ見れば進められます。

## 最初に見るもの

1. `docs/00_はじめに/start-to-tutorial-guide.md`
   - 導入から `tutorial-2` 完了までの一本道
   - 印刷・PDF保存用: `docs/00_はじめに/start-to-tutorial-guide.html`
2. `docs/02_作業ルール/redmine-guide.md`
   - Redmineの起動・初期化・課題投入・reset
   - 通常はRedmineで進捗を管理し、課題内容は`ticket.md`で読む
3. `packs/php/tickets/`
   - 課題の置き場
   - 1フォルダ = 1つの仕事
4. `reports/`
   - 自分の報告書・振り返りを書く場所

## 作業中によく見るもの

| 場所 | 役割 |
|---|---|
| Redmine(`http://127.0.0.1:8280`) | チケットの担当・見積・コメント・進捗管理 |
| `packs/php/tickets/` | 課題。まず `ticket.md` を読む |
| `packs/php/app/` | PHPアプリ本体。コード修正・テストの対象 |
| `docs/01_設計資料/` | 仕様書・DB設計・画面遷移図 |
| `docs/templates/` | 報告書・振り返りのひな形 |
| `reports/` | 自分の提出物 |

## 基本は触らないもの

| 場所 | 理由 |
|---|---|
| `tools/` | check や dist の内部ツール |
| `.github/` | GitHub Actions 設定 |
| `extensions/` | VS Code チューター拡張 |
| 制作者用の内部資料 | 配布物には入りません |
| master専用の模範資料 | 配布物には入りません |

## 迷ったら

詳しい地図は `docs/03_参考資料/folder-map.md` です。

ファイルを探すときは、VS Code でリポジトリの一番上を開き、**Ctrl+P → ファイル名**で移動してください。
