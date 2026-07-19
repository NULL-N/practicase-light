---
id: tutorial
title: 【チュートリアル】案件詳細の項目名が、ほかの画面と違う
level: 1
track: dev
type: fix
priority: normal
estimated_minutes: 30
role: developer
status: open
scope:
  - "packs/php/app/public/projects/show.php"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# tutorial: 案件詳細の項目名が、ほかの画面と違う

**起票: 早瀬(開発リーダー)**

本番のチケットに入る前に、**「直す流れ」を一周だけ肩慣らし**してもらいます。
題材は本物の(小さな)不具合です — CS 経由で「案件の一覧と詳細で、同じ情報なのに言葉が違う」という
指摘がありました。案件詳細(S-04)の表の項目名に、誤った表記が1つ紛れています。

## チュートリアルの見方(この課題だけの特別装備)

ブラウザで次の URL を開いてください(ポートを変えた人は自分の番号で):

```text
http://localhost:8180/login.php?tutorial=1
```

以降、**ログイン → 案件一覧 → 案件詳細**と進むあいだ、画面ごとに「**次の1歩**」だけが明るく表示され、
それ以外は暗くなります。ゲームのチュートリアルと同じで、どこを押して・どこを見ればいいかを
アプリが教えてくれるのはこの課題だけです。
表示をやめたいときは、吹き出しの「チュートリアル表示を終了する」を押します(再開は上の URL をもう一度)。

**VS Code 側の誘導(任意)**: チューター拡張(導入: `docs/00_はじめに/setup-guide.md` の「チューター拡張」)を入れていれば、
左の 🎓 パネルが VS Code 内の手順 — ファイルの場所・検索・check・報告 — も1歩ずつ案内します。

## やること(この順で)

1. Redmine へログインし、プロジェクト「PractiCase Light」でカスタムフィールド
   `PractiCase Ticket ID` が `tutorial` の issue を開く
   - 担当者を自分にして、status を **New → In Progress** にする
   - 「何分で終わりそうか」をコメント(note)へ一言書く(終わったとき見比べる —
     実務でずっと使う習慣の種)
   - Redmine を使う間、この `ticket.md` の front matter は変更しない。課題内容はこのファイル、
     進捗は Redmine が正本です
2. 上の URL から、**明るい場所をたどって案件詳細まで進み、症状を自分の目で見る** —
   明るい行の項目名を、直前に通った一覧の列名と見比べる
3. どちらの表記が正しいかを `support/spec.md` で確認する
4. **画面の文字でコードを探す**(この課題で覚えてほしい、いちばん大事な技):
   - VS Code で **Ctrl+Shift+F** を押す(**F** = Find。Ctrl+F が「このファイル内を検索」、
     Shift が付くと「**全ファイルを横断して検索**」になります)
   - 検索窓に、**画面に見えている誤った方の言葉**をそのまま入力する
   - 検索結果は **2箇所** 出ます: 1つは画面のファイル、もう1つは `tests/checks/` の中(この課題の
     合格条件=テスト)。**直すのは画面の方**です — 「検索には複数ヒットする。どれが現場かを見分ける」
     ところまで含めて、実務でそのまま使う目です。画面に見えている文字は、必ずどこかのコードが出しています
5. 1語直して保存し、check を実行する:

   ```text
   docker compose exec app php tools/check.php tutorial
   ```

   `結果: PASS` になるまで直します(FAIL のときは、何がダメかがメッセージに出ます)
6. `reports/tutorial_fix_report.md` を作り、**3行だけ**報告を書く(何が起きていたか / どこをどう直したか / check の結果)
7. Redmine の issue に `check tutorial: PASS` と報告ファイル名をコメントし、status を
   **In Progress → Resolved → Closed** にする — これで1周完了です

> **Redmine が使えないときだけ**: 学習は止めず、この `ticket.md` の front matter を
> `open` → `in_progress` → `resolved` → `closed` と更新して進めます。Redmine が戻っても
> 自動同期はされないため、必要なら後で手動で進捗を合わせます。

## この課題でやらないこと

ブランチ・Pull Request・仕様書の精読は、この課題では**使いません**。
それらの「実務の型」は、次の **T-001** で `docs/00_はじめに/first-ticket-walkthrough.md` と一緒に本気でやります。
ここでは「見る → 探す → 直す → check → 報告 → closed」の一周を体で覚えれば十分です。

## 完了条件

- `check tutorial` が PASS
- 3行の報告が reports/ にある
- Redmine の issue が Closed になり、PASS結果のコメントが残っている
  (fallback時は front matter が `closed`)

## 完了したら(次の一歩)

次は、少し実務に寄せた内容のチュートリアルです — `packs/php/tickets/01_チュートリアル/tutorial-2/ticket.md` を開く
(今度は「直す」ではなく「**作る**」を一周します)。その次が最初の本番課題 T-001 です。

> 詰まったら、このフォルダの `support/hints.md`(2段だけ)を開いてください。
