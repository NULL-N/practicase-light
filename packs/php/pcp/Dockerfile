FROM php:8.5-cli

# PCP(PractiCase Cloud Platform)— 教材内で完結する架空クラウドAPIのモックサーバー。
# 素の php -S だけで動かすため、追加パッケージ・追加拡張は入れない。
# ホストへポートは公開しない(docker-compose.yml 側でも publish しない)。
# Light 版は既定の1ワーカーで動かす(リクエストが長くブロックする機能が無いため)。
COPY server.php /opt/pcp/server.php

CMD ["php", "-S", "0.0.0.0:8080", "/opt/pcp/server.php"]
