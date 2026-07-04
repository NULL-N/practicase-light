# PractiCase チューター(VS Code 拡張)

PractiCase 教材の **VS Code 内チュートリアル誘導**。課題 tutorial の進め方を、
ファイルツリーのバッジ(👉)・エディタ内のライトアップ(対象行を明るく・他を薄く)・
サイドパネルの「次の1歩」で、チケット読みから closed まで1歩ずつ案内する。

- ブラウザ側のスポットライト(アプリ内 `?tutorial=1`)= **症状を見る場所**の誘導
- この拡張 = **作業する場所(VS Code)**の誘導
- 手順の中身は `.tutorial/steps.json`(教材側データ)。**文言の修正に拡張の再ビルドは不要**

## ライセンス

この拡張は PractiCase(Light)本体の一部であり、単独のライセンスは持ちません。
利用条件は、リポジトリ直下の README.md の「License / 利用条件」「準拠法・管轄」の節が正本です。

## セキュリティ設計(この拡張が「しないこと」)

学習者の PC で動くものなので、次を設計原則として実装している(extension.js 冒頭の「憲法」と対応):

| # | 原則 | 実装 |
|---|---|---|
| 1 | **ネットワーク通信をしない** | http/https/fetch/net を一切使わない。外部 URL を開く機能も `http://localhost:ポート` パターン以外は拒否 |
| 2 | **勝手にコマンドを実行しない** | child_process 不使用。ターミナルには check コマンドの**文字を置くだけ**(Enter は学習者)。コマンド文字列は拡張内に固定 |
| 3 | **steps.json はデータであって命令ではない** | 手順ファイルが指定できるのは、拡張内の**許可リスト(ACTIONS)にある操作の名前だけ**。改竄されても任意の操作は起きない |
| 4 | **ワークスペースの外に触らない** | パスは相対のみ(絶対パス・`..` を拒否)。書き込みは報告雛形の**新規作成のみ**(`wx` フラグ=既存ファイルは上書き不能) |
| 5 | **動的コード実行をしない** | eval / new Function / 動的 require なし。JSON は JSON.parse のみ |
| 6 | **Webview を閉じる** | CSP(nonce)で外部リソース遮断。パネルからのメッセージも許可リスト検証 |
| 7 | **Workspace Trust 必須** | 信頼していないフォルダでは起動しない(package.json で宣言) |

**監査の仕方**: 依存パッケージ 0・ビルド工程なしの素の JavaScript 1ファイル(`extension.js`)。
全挙動はそのファイルを読めば確認できる。`grep -nE "http|fetch|child_process|eval" extension.js` が
コメント以外にヒットしないことが、原則 1/2/5 の機械的な確認になる。

## 使い方(学習者)

1. VS Code で教材フォルダを開く(Workspace Trust を「信頼する」)
2. 初回は**エディタの右側にクエストログが自動で開く**
   (2回目以降は画面下の **🎓** をクリック、または Ctrl+Shift+P → 「PractiCase」)
3. 「チュートリアルを始める」→ 以降はログの案内どおり。作業ファイルは常に左側に開くので、
   タブをいくら切り替えてもログは隠れない。**「できた、次へ」は画面下にも常駐**しているので、
   ログを見ていなくても進められる

拡張が無くても課題は完走できる(`docs/00_はじめに/first-ticket-walkthrough.md` とブラウザ誘導が従来どおり機能する)。
この拡張は「あると迷わない」ためのレイヤー。

## 導入(開発・検証時)

Marketplace には公開していない。ローカルで使う方法は2つ:

- **.vsix 方式(推奨)**: このフォルダに同梱してある `.vsix` ファイルを、VS Code の
  「Extensions → … → Install from VSIX」で選ぶ(手順の詳細: `docs/00_はじめに/setup-guide.md`)
- **フォルダ複製方式(依存ゼロ・確認用)**: このフォルダを丸ごと
  `%USERPROFILE%\.vscode\extensions\practicase-tutor` にコピーして VS Code を再起動

同梱の `.vsix` は `npx @vscode/vsce package`(要 Node.js)でこのフォルダから再生成できる
(ソースは全て同梱 — 中身を監査してから、自分でパッケージし直して入れることもできる)。

## 構成

```text
extensions/practicase-tutor/
  package.json    … 拡張の宣言(依存ゼロ・Workspace Trust 必須・activationEvents 限定)
  extension.js    … 全ロジック(素の JavaScript 1ファイル)
  README.md       … このファイル
.tutorial/
  steps.json      … 手順データ(教材側。表示文言と許可済み操作名のみ)
```
