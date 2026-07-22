# T-000 セルフチェック(rubric)

このファイル自体は編集しません。commit前は「提出前」、Pull RequestをmergeしてRedmineを
Closedにした後は「提出後」を確認します。

## 提出前(commit前)

- [ ] READMEの導入手順を完了したか
      (未完了の項目があった場合だけ`setup-guide.md`を参照したか)

- [ ] Redmineで担当者を自分にし、見積をコメントして送信し、
      ステータスを`New`から`In Progress`へ変更したか

- [ ] ブラウザからアプリへログインし、案件一覧が表示されることを確認したか

- [ ] `reports/T-000_setup_report.md`を作成し、各見出しを自分の環境の内容で埋めたか

- [ ] 詰まった点を「何が起きたか / どう調べたか / どう解決したか」の形で書いたか
      (詰まらなかった場合は「特になし」と書いたか)

- [ ] `docker compose exec app php tools/check.php T-000`を実行し、
      最後に`結果: PASS`が表示されたか

- [ ] `git status --short`を確認し、T-000と関係のない変更が含まれていないか

- [ ] Pull Requestの本文へ書くcheck結果と`reports/T-000_setup_report.md`を確認したか

## 提出後

- [ ] 作業ブランチをpushし、Pull RequestをGitHubでmergeしたか

- [ ] ローカルmainを`git pull --ff-only`で同期し、作業ブランチを削除したか

- [ ] Pull Requestをmergeした後、セットアップで分かったことを振り返りとして1文書いたか

- [ ] RedmineへPASS結果・Pull RequestのURL・報告ファイル名・振り返りをコメントし、`Closed`にしたか

## Redmineが使えない場合だけ

- [ ] 着手時に`ticket.md`のfront matterを`open`から`in_progress`へ変更したか

- [ ] 完了時にfront matterを`in_progress`から`resolved`、`closed`へ変更したか

## 任意確認

- 3ロールすべてでログインし、画面の違い(見えるもの・できること)を観察した
