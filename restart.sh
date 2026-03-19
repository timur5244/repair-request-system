#!/usr/bin/env bash
set -euo pipefail

PROJECT_NAME="${PROJECT_NAME:-repair_requests}"
docker compose -p "$PROJECT_NAME" down
docker compose -p "$PROJECT_NAME" up -d --build
docker compose -p "$PROJECT_NAME" ps

