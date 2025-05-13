#!/bin/sh
set -e

# テストモードの場合
if [ "$1" = "test" ]; then
  exec npm test
fi

# コマンドを実行
exec "$@" 