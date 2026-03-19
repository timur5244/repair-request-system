#!/usr/bin/env bash
set -euo pipefail

PROJECT_NAME="${PROJECT_NAME:-repair_requests}"

if ! command -v docker >/dev/null 2>&1; then
  echo "docker not found"
  exit 1
fi
if ! docker compose version >/dev/null 2>&1; then
  echo "docker compose not found"
  exit 1
fi

echo "[start] build + up"
docker compose -p "$PROJECT_NAME" up -d --build

echo "[start] status"
docker compose -p "$PROJECT_NAME" ps

echo "[start] app logs (last 40 lines)"
docker compose -p "$PROJECT_NAME" logs --tail=40 app

