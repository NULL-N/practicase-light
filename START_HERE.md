# はじめに — 2つの始め方

PractiCase へようこそ。この教材は**2つの始め方**があります。好きな方を選んでください
(あとから切り替えても大丈夫です)。

フォルダ構造で迷ったら、まず **[LEARN_HERE.md](LEARN_HERE.md)** を開いてください。
詳しい地図は **[docs/03_参考資料/folder-map.md](docs/03_参考資料/folder-map.md)** です。

---

## 🎓 A. 画面が案内してくれる「チュートリアル付き」で始める(おすすめ)

導入からチュートリアル2本の完了までは、**[docs/00_はじめに/start-to-tutorial-guide.md](docs/00_はじめに/start-to-tutorial-guide.md)**
を手元に置いて進めてください。印刷・PDF保存しやすい
**[HTML版](docs/00_はじめに/start-to-tutorial-guide.html)** もあります。

VS Code に**チュートリアル拡張**を入れると、最初の課題を画面が手取り足取り案内します
——「どのフォルダを見るか」「どの行を直すか」「どう確認するか」を、光る印とクエストログで
1歩ずつ。ゲームのチュートリアルのように進められます。

**入れ方(3ステップ)**

1. VS Code でこのフォルダを開く(「このフォルダーの作成者を信頼しますか?」は **信頼する** を選ぶ)
2. `Ctrl+Shift+P` を押して **「Extensions: Install from VSIX」** と入力して選び、
   `extensions/practicase-tutor/` フォルダの中の `.vsix` ファイルを選ぶ
3. `Ctrl+Shift+P` →**「Reload Window」**(ウィンドウの再読み込み)を選ぶ

入れ終えると、画面の右側に**クエストログ**が自動で開きます。あとはその案内どおりに進むだけ。
(この拡張は**通信なし・無料・オフライン動作**。安全設計の説明は
`extensions/practicase-tutor/README.md`。入れたくなければ B へ)

---

## 📖 B. 文章の手順書だけで始める(拡張なし)

拡張を使わなくても、**すべての課題は文章の手順書だけで最後まで完走できます**。
その場合は、まず **[docs/00_はじめに/start-to-tutorial-guide.md](docs/00_はじめに/start-to-tutorial-guide.md)** を開いてください。
導入、T-000、tutorial、tutorial-2 までの順番がまとまっています。

---

どちらの道でも、最初にやることは同じ —— 環境構築(**T-000**)です。
README と `docs/00_はじめに/setup-guide.md` がその手順を案内します。
