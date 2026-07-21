# T-000 セルフチェック(rubric)

- [ ] setup-guide の手順を「読んでから」実行したか(コピペで流しただけになっていないか)

- [ ] チェックコマンド(`docker compose exec app php tools/check.php T-000`)を実行し、
      最後に「結果: PASS」が表示されたか

- [ ] (任意)アプリ全体を眺めたくなったら、3ロールすべてでログインして画面の違い(見えるもの・できること)を観察したか

- [ ] 完了報告を書いたか — `docs/templates/setup_report.md` を
      `reports/T-000_setup_report.md` へコピーし、
      各見出しを自分の環境の内容で埋める(第三者が同じ環境を作れる粒度で)

- [ ] 詰まった点を「何が起きて・どう調べて・どう解決したか」の形で書いたか(詰まらなかったならそれでよい)

- [ ] Redmineで担当者を自分にし、見積をコメントして送信し、
      `New` → `In Progress` → `Resolved` → `Closed`と進めたか

- [ ] 完了報告をcommitしてmainへローカルmergeし、現在のbranchがmain、
      `git status --short`に何も表示されない状態にしたか

- [ ] Redmineが使えない場合だけ、front matterの`status`を
      `open` → `in_progress` → `resolved` → `closed`と更新したか
