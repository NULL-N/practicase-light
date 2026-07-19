# PractiCase Redmine bootstrap(冪等)。
#
# 実行方法(リポジトリルートで):
#   docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -' < tools/redmine/bootstrap.rb
# (SECRET_KEY_BASE の受け渡しが要るのは、公式イメージの entrypoint がこの変換を
#  web プロセスにだけ行うため — exec は entrypoint を通らない)
#
# 役割: REST API 有効化 / 既定データ投入 / トラッカー / プロジェクト /
#       学習者ユーザー / seed 専用ユーザー / カスタムフィールド「PractiCase Ticket ID」を
#       冪等に作成する。
# 方針:
# - DB への直接 SQL・管理画面の手動操作は使わない(このスクリプトが唯一の初期化手段)
# - 何度実行しても安全(2回目以降は no-op)。作成/更新した項目だけを報告する
# - 認証値(パスワード・API キー)は標準出力へ出さない
# - seed 用の API キーはソースコードに固定しない。Redmine が生成した値を
#   runtime 設定ファイル(named volume)へ保存し、seed ツールがそこから読む。
#   再実行しても既存キーを不要にローテートしない(reset 時だけ新しい値になる)

PROJECT_IDENTIFIER = 'practicase-light'
PROJECT_NAME = 'PractiCase Light'
TRACKER_NAME = 'PractiCase課題'
CUSTOM_FIELD_NAME = 'PractiCase Ticket ID'
LEARNER_LOGIN = 'practicase'
LEARNER_PASSWORD = 'practicase123' # ローカル専用(教材世界の値)
SEED_LOGIN = 'practicase-seed'
SEED_ROLE_NAME = 'PractiCase Seed'
RUNTIME_DIR = '/practicase-runtime' # compose の redmine_runtime volume
RUNTIME_FILE = File.join(RUNTIME_DIR, 'seed-credentials.json')

changes = []

# 1. 既定データ(ステータス・ロール・ワークフロー等)。空なら英語で投入する —
#    ステータス名 New / In Progress / Resolved / Closed を status マッピングの正とするため
if Redmine::DefaultData::Loader.no_data?
  Redmine::DefaultData::Loader.load('en')
  changes << 'default-data: 投入(en)'
end

# 2. REST API を有効化(seed は REST 経由で issue を作る)
if Setting.rest_api_enabled != '1'
  Setting.rest_api_enabled = '1'
  changes << 'rest_api: 有効化'
end

# 3. admin の出荷時既定パスワードを無効化し、初回変更強制を解除する。
#    既定値がまだ有効な環境だけを自己修復し、手動変更済みのパスワードは触らない。
#    seed は admin を使わない — 過去の bootstrap が admin に付けた API キーは
#    ここで無効化する(旧方式の残骸を残さない自己修復)
admin = User.find_by_login('admin') or raise 'admin ユーザーが見つかりません'
unless User.default_admin_account_changed?
  random_password = SecureRandom.hex(32)
  admin.password = random_password
  admin.password_confirmation = random_password
  admin.must_change_passwd = false
  admin.save!
  changes << 'admin: 出荷時既定パスワードを無効化(値は表示しない)'
end
if admin.reload.must_change_passwd?
  admin.update_column(:must_change_passwd, false)
  changes << 'admin: 初回パスワード変更の強制を解除'
end
removed = Token.where(user_id: admin.id, action: 'api').delete_all
changes << 'admin: API キーを無効化(seed は専用ユーザーへ移行)' if removed > 0

# 4. トラッカー(既定ステータス New)+ ワークフロー(Bug のルールを複製)
new_status = IssueStatus.find_by(name: 'New') or raise 'ステータス New がありません(既定データ未投入?)'
tracker = Tracker.find_by(name: TRACKER_NAME)
if tracker.nil?
  tracker = Tracker.create!(name: TRACKER_NAME, default_status_id: new_status.id)
  changes << 'tracker: 作成'
end
if WorkflowTransition.where(tracker_id: tracker.id).none?
  source = Tracker.find_by(name: 'Bug') or raise 'ワークフロー複製元の Bug トラッカーがありません'
  copied = 0
  WorkflowTransition.where(tracker_id: source.id).find_each do |rule|
    WorkflowTransition.create!(rule.attributes.except('id').merge('tracker_id' => tracker.id))
    copied += 1
  end
  changes << "workflow: Bug から #{copied} ルールを複製"
end

# 5. カスタムフィールド「PractiCase Ticket ID」(教材IDの保持・seed の冪等キー)
cf = IssueCustomField.find_by(name: CUSTOM_FIELD_NAME)
if cf.nil?
  cf = IssueCustomField.create!(
    name: CUSTOM_FIELD_NAME, field_format: 'string',
    is_for_all: true, is_filter: true, searchable: true
  )
  changes << 'custom-field: 作成'
end
unless cf.trackers.include?(tracker)
  cf.trackers << tracker
  changes << 'custom-field: トラッカーへ割り当て'
end

# 6. プロジェクト(identifier は固定・非公開=ログイン必須)
project = Project.find_by(identifier: PROJECT_IDENTIFIER)
if project.nil?
  project = Project.create!(
    name: PROJECT_NAME, identifier: PROJECT_IDENTIFIER,
    is_public: false, enabled_module_names: ['issue_tracking']
  )
  changes << 'project: 作成'
end
unless project.trackers.include?(tracker)
  project.trackers << tracker
  changes << 'project: トラッカーを有効化'
end

# 7. 学習者ユーザー(一般ユーザー・プロジェクトの Developer メンバー)
learner = User.find_by_login(LEARNER_LOGIN)
if learner.nil?
  learner = User.new(
    login: LEARNER_LOGIN, firstname: 'PractiCase', lastname: '学習者',
    mail: 'learner@example.com', language: 'ja'
  )
  learner.password = LEARNER_PASSWORD
  learner.password_confirmation = LEARNER_PASSWORD
  learner.save!
  changes << 'learner: 作成'
end
developer_role = Role.find_by(name: 'Developer') or raise 'ロール Developer がありません(既定データ未投入?)'
manager_role = Role.find_by(name: 'Manager') or raise 'ロール Manager がありません(既定データ未投入?)'
member = Member.find_by(project_id: project.id, user_id: learner.id)
if member.nil?
  Member.create!(project: project, principal: learner, roles: [developer_role])
  changes << 'learner: プロジェクトメンバーへ登録(Developer)'
else
  # 既存環境の自己修復: 過去の bootstrap が付与した Manager を Developer へ是正する。
  # Member の roles が一瞬でも空にならないよう、先に Developer を追加してから Manager を除去する
  # (Manager ロール定義自体や、他に手動で付与されたロールがあってもそれは削除しない)
  unless member.roles.include?(developer_role)
    member.roles << developer_role
    changes << 'learner: Developer権限を付与'
  end
  if member.roles.include?(manager_role)
    member.roles.delete(manager_role)
    changes << 'learner: Manager権限を除去'
  end
end

# 8. seed 専用ユーザー(admin ではない・issue の参照/作成/更新に必要な最小ロールのみ)
seed_role = Role.find_by(name: SEED_ROLE_NAME)
if seed_role.nil?
  seed_role = Role.create!(
    name: SEED_ROLE_NAME,
    permissions: %i[view_issues add_issues edit_issues]
  )
  changes << 'seed-role: 作成(view/add/edit issues のみ)'
end
# seed ロール用のワークフロー(初期 status 指定つき作成に必要)。Manager の行を雛形に複製
if WorkflowTransition.where(tracker_id: tracker.id, role_id: seed_role.id).none?
  template_rules = WorkflowTransition.where(tracker_id: tracker.id, role_id: manager_role.id)
  raise 'seed ロール複製元のワークフローがありません' if template_rules.none?
  template_rules.find_each do |rule|
    WorkflowTransition.create!(rule.attributes.except('id').merge('role_id' => seed_role.id))
  end
  changes << 'seed-role: ワークフローを複製'
end
seed_user = User.find_by_login(SEED_LOGIN)
if seed_user.nil?
  seed_user = User.new(
    login: SEED_LOGIN, firstname: 'PractiCase', lastname: 'Seed',
    mail: 'seed@example.com', language: 'ja', admin: false
  )
  random_password = SecureRandom.hex(16) # ログイン用途なし(API 専用)。どこにも出力しない
  seed_user.password = random_password
  seed_user.password_confirmation = random_password
  seed_user.save!
  changes << 'seed-user: 作成(admin ではない)'
end
unless Member.exists?(project_id: project.id, user_id: seed_user.id)
  Member.create!(project: project, principal: seed_user, roles: [seed_role])
  changes << 'seed-user: プロジェクトメンバーへ登録(PractiCase Seed)'
end

# 9. seed 用 API キー(Redmine 生成値のまま使う)と runtime 設定の保存。
#    既存キーがあれば再利用する — 再実行で不要にローテートしない
raise "runtime ディレクトリがありません: #{RUNTIME_DIR}(compose の redmine_runtime volume を確認してください)" unless Dir.exist?(RUNTIME_DIR)
seed_token = Token.where(user_id: seed_user.id, action: 'api').first
if seed_token.nil?
  seed_token = Token.create!(user: seed_user, action: 'api')
  changes << 'seed-user: API キーを生成'
end
runtime_payload = JSON.pretty_generate(
  api_key: seed_token.value,
  custom_field_id: cf.id,
  tracker_id: tracker.id,
  project_identifier: PROJECT_IDENTIFIER
)
if !File.exist?(RUNTIME_FILE) || File.read(RUNTIME_FILE) != runtime_payload
  File.write(RUNTIME_FILE, runtime_payload)
  File.chmod(0o600, RUNTIME_FILE)
  changes << 'runtime: 認証情報を保存(値は表示しない)'
end

# 完全性の自己検証(重複が無いこと)
raise 'tracker 重複' unless Tracker.where(name: TRACKER_NAME).count == 1
raise 'custom-field 重複' unless IssueCustomField.where(name: CUSTOM_FIELD_NAME).count == 1
raise 'project 重複' unless Project.where(identifier: PROJECT_IDENTIFIER).count == 1
raise 'learner 重複' unless User.where(login: LEARNER_LOGIN).count == 1
learner_roles = Member.find_by(project_id: project.id, user_id: learner.id)&.roles || []
raise 'learner のロールが Developer のみになっていません' \
  unless learner_roles.include?(developer_role) && !learner_roles.include?(manager_role)
raise 'seed-user 重複' unless User.where(login: SEED_LOGIN).count == 1
raise 'seed-user が admin になっています' if seed_user.reload.admin?
seed_roles = Member.find_by(project_id: project.id, user_id: seed_user.id)&.roles || []
raise 'seed-user のロールが PractiCase Seed のみになっていません' \
  unless seed_roles.map(&:name) == [SEED_ROLE_NAME]
raise 'runtime 設定が読めません' unless File.readable?(RUNTIME_FILE)
raise 'admin の出荷時既定パスワードが有効なままです' unless User.default_admin_account_changed?

if changes.empty?
  puts 'bootstrap: no-op(すべて設定済み)'
else
  puts "bootstrap: #{changes.size} 件を作成/更新"
  changes.each { |c| puts "  - #{c}" }
end
puts 'integrity: tracker=1 custom-field=1 project=1 learner=1 learner-role=developer-only ' \
     'seed-user=non-admin seed-role=minimal admin-default-password=disabled runtime=saved'
