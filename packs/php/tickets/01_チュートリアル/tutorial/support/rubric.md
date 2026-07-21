# tutorial セルフレビュー観点

RedmineのチケットをClosedにする前に、自分で確認する。

- [ ] 項目名が「時間単価」になっている(S-03 の列名・F-02 の用語と一致)
- [ ] 変更したのは**その1語だけ**か(ついでの整形・無関係な変更をしていない)
- [ ] `check tutorial` が PASS している
- [ ] `reports/tutorial_fix_report.md` に3行の報告がある(何が起きていたか / どこをどう直したか / check の結果)
- [ ] 作業内容をcommitし、mainへmergeした
- [ ] 作業ブランチを削除し、現在のブランチがmain
- [ ] `git status --short`に何も表示されない
- [ ] RedmineのチケットにPASS結果をコメントし、Closedにした
  (fallback時は`ticket.md`のstatusが`closed`)

## ふりかえりの種(任意)

「画面に見えている文字で全ファイル検索する」— この探し方は、この先ほぼ毎回使います。
どの言葉で検索したら1発で見つかったか、を1行メモしておくと次が速くなります。
