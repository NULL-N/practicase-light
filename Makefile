# NULL-N PractiCase 補助ショートカット
# make が無くても全操作は docker compose で完結する(正式手順は docs 参照):
#   docker compose exec app php tools/check.php T-001
.PHONY: up down init-db test check

up:
	docker compose up -d

down:
	docker compose down

init-db:
	docker compose exec app php tools/init-db.php

test:
	docker compose exec app php packs/php/app/tests/run.php

# 使い方: make check T=T-001
check:
	docker compose exec app php tools/check.php $(T)
