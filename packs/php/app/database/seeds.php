<?php

declare(strict_types=1);

use App\Support\Clock;

// 初期データ定義。tools/init-db.php が適用する。
// 人物・企業は docs/03_参考資料/world.md の利用者名簿に対応する。
// 日付は基準日(Clock::today)からの相対で生成し、実行日に依存して壊れないようにする(ARC-5)。

$d = fn (int $days): string => Clock::today()->modify(sprintf('%+d days', $days))->format('Y-m-d');

return [
    // company / engineer の参照はこの配列の並び順(1始まり)・email で行う
    'companies' => [
        ['name' => '株式会社モクレン商事', 'contact_email' => 'contact@mokuren.example.com'],
        ['name' => '株式会社アオバ計画', 'contact_email' => 'info@aoba.example.com'],
    ],
    'users' => [
        ['name' => '小野寺 玲', 'email' => 'admin@example.com', 'role' => 'admin', 'company' => null],
        ['name' => '田淵 亮', 'email' => 'tabuchi@example.com', 'role' => 'client', 'company' => 1],
        ['name' => '志村 恵', 'email' => 'shimura@example.com', 'role' => 'client', 'company' => 2],
        ['name' => '桐生 蒼', 'email' => 'kiryu@example.com', 'role' => 'engineer', 'company' => null],
        ['name' => '浅葱 純', 'email' => 'asagi@example.com', 'role' => 'engineer', 'company' => null],
        ['name' => '柚木 涼太', 'email' => 'yuzuki@example.com', 'role' => 'engineer', 'company' => null],
    ],
    // シード要件(features.md 付録): open(締切前)/open(締切超過)/closed/リモート可/不可/定員1 を各1件以上
    'projects' => [
        [
            'company' => 1,
            'title' => 'ECサイトの商品検索改善',
            'description' => '自社ECサイトの商品検索が遅く、絞り込み条件も不足しています。検索処理の改善と条件追加をお願いします。',
            'hourly_rate' => 3500, 'capacity' => 2,
            'deadline' => $d(14), 'work_start_on' => $d(21),
            'is_remote' => 1, 'status' => 'open',
        ],
        [
            'company' => 1,
            'title' => '社内勤怠ツールの不具合修正',
            'description' => '月末締め処理でエラーが出ることがあります。原因調査と修正をお願いします。出社での作業になります。',
            'hourly_rate' => 3000, 'capacity' => 1,
            'deadline' => $d(7), 'work_start_on' => $d(10),
            'is_remote' => 0, 'status' => 'open',
        ],
        [
            'company' => 1,
            'title' => 'レガシーPHPシステムの現状調査',
            'description' => '古い業務システムの構成調査とドキュメント化。改修計画の材料にします。',
            'hourly_rate' => 4000, 'capacity' => 1,
            'deadline' => $d(-3), 'work_start_on' => $d(5),
            'is_remote' => 1, 'status' => 'open',
        ],
        [
            'company' => 2,
            'title' => '店舗予約フォームの新設',
            'description' => '自社サイトに予約フォームを追加したいです。入力チェックと完了メールまでお願いします。',
            'hourly_rate' => 3200, 'capacity' => 3,
            'deadline' => $d(21), 'work_start_on' => $d(30),
            'is_remote' => 1, 'status' => 'open',
        ],
        [
            'company' => 2,
            'title' => 'キャンペーンLPの軽微修正',
            'description' => '文言差し替えと画像の入れ替え。社内環境での作業をお願いします。',
            'hourly_rate' => 2800, 'capacity' => 1,
            'deadline' => $d(-10), 'work_start_on' => $d(-5),
            'is_remote' => 0, 'status' => 'closed',
        ],
        [
            'company' => 1,
            'title' => '社外向けAPIドキュメントの整備',
            'description' => '取引先に公開しているAPIのドキュメントが古いままです。実装との差分を洗い出して更新してください。',
            'hourly_rate' => 3000, 'capacity' => 2,
            'deadline' => $d(10), 'work_start_on' => $d(14),
            'is_remote' => 1, 'status' => 'open',
        ],
        // ここから下は表示密度のための追加データ(課題の再現条件は上の6件が担う。既存6件は変更しない)
        [
            'company' => 2,
            'title' => '会員向けメールマガジン配信の不具合調査',
            'description' => '毎週金曜に配信しているメールマガジンが、一部の会員にだけ届かないことがあります。配信ログの調査と原因の切り分け、必要であれば修正までお願いします。配信基盤は社内の小さな PHP バッチです。',
            'hourly_rate' => 3600, 'capacity' => 1,
            'deadline' => $d(12), 'work_start_on' => $d(18),
            'is_remote' => 1, 'status' => 'open',
        ],
        [
            'company' => 1,
            'title' => '商品画像アップロード機能の改善',
            'description' => 'ECサイトの商品登録で、画像のアップロードに時間がかかるとの声が社内から出ています。画像のリサイズ処理の見直しと、失敗時のエラーメッセージ改善をお願いします。',
            'hourly_rate' => 3300, 'capacity' => 2,
            'deadline' => $d(18), 'work_start_on' => $d(25),
            'is_remote' => 1, 'status' => 'open',
        ],
        [
            'company' => 2,
            'title' => '棚卸し支援ツールの画面改修',
            'description' => '倉庫で使っている棚卸し支援ツールの入力画面が使いづらく、現場から改善要望が出ています。実機を触りながらの改修になるため、出社での作業をお願いします。',
            'hourly_rate' => 3100, 'capacity' => 1,
            'deadline' => $d(9), 'work_start_on' => $d(14),
            'is_remote' => 0, 'status' => 'open',
        ],
        [
            'company' => 1,
            'title' => '新入社員向け業務マニュアルのWeb化',
            'description' => '紙とExcelで管理している業務マニュアルを、社内ポータルで見られる形に移行したいです。構成の整理と静的ページ化、検索できる目次づくりまでお願いします。',
            'hourly_rate' => 2900, 'capacity' => 2,
            'deadline' => $d(25), 'work_start_on' => $d(35),
            'is_remote' => 1, 'status' => 'open',
        ],
    ],
    // 応募 4状態(applied / accepted / rejected / withdrawn)を各1件以上
    'applications' => [
        ['project' => 1, 'engineer' => 'kiryu@example.com', 'status' => 'applied',
         'message' => '検索改善の経験があります。よろしくお願いします。', 'applied_days_ago' => 2, 'decided_days_ago' => null],
        ['project' => 1, 'engineer' => 'asagi@example.com', 'status' => 'accepted',
         'message' => 'ECサイトの保守を3年担当していました。', 'applied_days_ago' => 5, 'decided_days_ago' => 3],
        ['project' => 2, 'engineer' => 'yuzuki@example.com', 'status' => 'rejected',
         'message' => '勤怠系は未経験ですが挑戦したいです。', 'applied_days_ago' => 4, 'decided_days_ago' => 2],
        ['project' => 6, 'engineer' => 'kiryu@example.com', 'status' => 'withdrawn',
         'message' => 'ドキュメント整備は得意です。', 'applied_days_ago' => 6, 'decided_days_ago' => 1],
        // 追加分(表示密度用)
        ['project' => 7, 'engineer' => 'asagi@example.com', 'status' => 'applied',
         'message' => 'メール配信基盤の運用経験があります。ログ調査から入らせてください。', 'applied_days_ago' => 1, 'decided_days_ago' => null],
        ['project' => 8, 'engineer' => 'yuzuki@example.com', 'status' => 'applied',
         'message' => '画像処理まわりの改善は前職でも担当していました。', 'applied_days_ago' => 2, 'decided_days_ago' => null],
        ['project' => 10, 'engineer' => 'kiryu@example.com', 'status' => 'applied',
         'message' => 'ドキュメントの構造化と静的サイト化の経験があります。', 'applied_days_ago' => 1, 'decided_days_ago' => null],
    ],
];
