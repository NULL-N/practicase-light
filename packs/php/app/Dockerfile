FROM php:8.5-cli

# git はチェックツール(scope 検査)が diff を取るために使う
RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && apt-get clean \
    && git config --system --add safe.directory /var/www/practicase
