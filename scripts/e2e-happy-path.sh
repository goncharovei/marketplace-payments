#!/usr/bin/env bash
#
# E2E Happy Path smoke test.
#
# Runs the full Order → Payment → Payout flow through RabbitMQ with all
# three Ecotone consumers running in the background. Verifies the final
# state of all aggregates and the Saga.
#
# Usage:
#   ./scripts/e2e-happy-path.sh              # run once, clean state first
#   ./scripts/e2e-happy-path.sh --no-clean   # skip DB/queue cleanup
#   ./scripts/e2e-happy-path.sh --keep-runn  # leave consumers running on exit
#
# Exit codes:
#   0 — happy path completed successfully
#   1 — prerequisite check failed
#   2 — timeout waiting for saga completion
#   3 — final state mismatch
#
set -euo pipefail

# ─────────────────────────────────────────────────────────────
# Configuration
# ─────────────────────────────────────────────────────────────
CLEAN_STATE=1
KEEP_RUNNING=0
TIMEOUT_SECONDS=30

while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-clean)   CLEAN_STATE=0; shift ;;
    --keep-runn*) KEEP_RUNNING=1; shift ;;
    -h|--help)
      sed -n '2,20p' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *) echo "Unknown option: $1"; exit 1 ;;
  esac
done

# ─────────────────────────────────────────────────────────────
# Colors
# ─────────────────────────────────────────────────────────────
BOLD='\033[1m'
DIM='\033[2m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
RESET='\033[0m'

step()  { echo ""; echo -e "${BOLD}${CYAN}▶ $1${RESET}"; }
ok()    { echo -e "${GREEN}  ✓ $1${RESET}"; }
warn()  { echo -e "${YELLOW}  ! $1${RESET}"; }
fail()  { echo -e "${RED}  ✗ $1${RESET}"; }

# ─────────────────────────────────────────────────────────────
# Prerequisites
# ─────────────────────────────────────────────────────────────
step "Checking prerequisites"

for svc in order-app payment-app payout-app order-db payment-db payout-db rabbitmq; do
  if ! docker compose ps --format '{{.Name}} {{.State}}' | grep -q "mp-${svc} running"; then
    fail "Container mp-${svc} is not running"
    echo "  Run: docker compose up -d"
    exit 1
  fi
done
ok "All containers are running"

if ! command -v jq >/dev/null 2>&1; then
  warn "jq not installed — some output will be less readable"
fi

# ─────────────────────────────────────────────────────────────
# Cleanup state
# ─────────────────────────────────────────────────────────────
if [[ $CLEAN_STATE -eq 1 ]]; then
  step "Cleaning state"

  # Stop any existing consumer processes for this run
  pkill -f "ecotone:run order_service"   2>/dev/null || true
  pkill -f "ecotone:run payment_service" 2>/dev/null || true
  pkill -f "ecotone:run payout_service"  2>/dev/null || true

  docker compose exec -T rabbitmq rabbitmqctl purge_queue distributed_order_service   >/dev/null 2>&1 || true
  docker compose exec -T rabbitmq rabbitmqctl purge_queue distributed_payment_service >/dev/null 2>&1 || true
  docker compose exec -T rabbitmq rabbitmqctl purge_queue distributed_payout_service  >/dev/null 2>&1 || true
  ok "RabbitMQ queues purged"

  docker compose exec -T order-app php artisan tinker --execute="
    DB::table('ecotone_deduplication')->truncate();
    DB::table('ecotone_document_store')->truncate();
  " >/dev/null 2>&1 || true
  ok "Ecotone state reset"

  docker compose exec -T order-db   psql -U order_user   -d order_db   -c "TRUNCATE orders CASCADE;"            >/dev/null 2>&1 || true
  docker compose exec -T payment-db psql -U payment_user -d payment_db -c "TRUNCATE payments CASCADE;"          >/dev/null 2>&1 || true
  docker compose exec -T payout-db  psql -U payout_user  -d payout_db  -c "TRUNCATE payouts, seller_balances CASCADE;" >/dev/null 2>&1 || true
  ok "Business data truncated"
else
  step "Skipping state cleanup (--no-clean)"
fi

# ─────────────────────────────────────────────────────────────
# Start consumers
# ─────────────────────────────────────────────────────────────
step "Starting Ecotone consumers in background"

CONSUMER_LOG_DIR=$(mktemp -d)
ORDER_PID=""
PAYMENT_PID=""
PAYOUT_PID=""

cleanup() {
  echo ""
  step "Stopping consumers"
  [[ -n "$ORDER_PID"   ]] && kill "$ORDER_PID"   2>/dev/null || true
  [[ -n "$PAYMENT_PID" ]] && kill "$PAYMENT_PID" 2>/dev/null || true
  [[ -n "$PAYOUT_PID" ]] && kill "$PAYOUT_PID"   2>/dev/null || true
  wait 2>/dev/null || true

  if [[ $KEEP_RUNNING -eq 0 ]]; then
    rm -rf "$CONSUMER_LOG_DIR"
  else
    echo -e "${DIM}  Consumer logs kept in: $CONSUMER_LOG_DIR${RESET}"
  fi
}

trap cleanup EXIT

docker compose exec -T order-app   php artisan ecotone:run order_service   -vvv \
  > "${CONSUMER_LOG_DIR}/order.log" 2>&1 &
ORDER_PID=$!

docker compose exec -T payment-app php artisan ecotone:run payment_service -vvv \
  > "${CONSUMER_LOG_DIR}/payment.log" 2>&1 &
PAYMENT_PID=$!

docker compose exec -T payout-app  php artisan ecotone:run payout_service  -vvv \
  > "${CONSUMER_LOG_DIR}/payout.log" 2>&1 &
PAYOUT_PID=$!

sleep 4
ok "Consumers started (PIDs: $ORDER_PID, $PAYMENT_PID, $PAYOUT_PID)"

# ─────────────────────────────────────────────────────────────
# Place order
# ─────────────────────────────────────────────────────────────
step "Placing order (buyer-e2e → seller-e2e, 2500 RUB)"

ORDER_ID=$(docker compose exec -T order-app php artisan tinker --execute="
\$bus = app(\Ecotone\Modelling\CommandBus::class);
echo \$bus->send(new \App\Application\Command\PlaceOrderCommand(
    buyerId: 'buyer-e2e',
    sellerId: 'seller-e2e',
    items: [['productId' => 'p1', 'quantity' => 1, 'amount' => 2500, 'currency' => 'RUB']],
))->toString();
" 2>/dev/null | tail -n 1 | tr -d '[:space:]')

if [[ -z "$ORDER_ID" ]]; then
  fail "Failed to place order"
  exit 1
fi
ok "Order ID: $ORDER_ID"

# ─────────────────────────────────────────────────────────────
# Start payment
# ─────────────────────────────────────────────────────────────
step "Starting payment"

docker compose exec -T order-app php artisan tinker --execute="
app(\Ecotone\Modelling\CommandBus::class)->send(
    new \App\Application\Command\StartPaymentCommand('$ORDER_ID')
);
" >/dev/null 2>&1
ok "payment.capture sent"

# ─────────────────────────────────────────────────────────────
# Wait for saga completion
# ─────────────────────────────────────────────────────────────
step "Waiting for Saga to complete (timeout: ${TIMEOUT_SECONDS}s)"

STATE=""
for i in $(seq 1 "$TIMEOUT_SECONDS"); do
  STATE=$(docker compose exec -T order-db psql -U order_user -d order_db -tAc \
    "SELECT document->>'state' FROM ecotone_document_store
     WHERE document->>'orderId' = '$ORDER_ID';" 2>/dev/null | tr -d '[:space:]')

  printf "\r  ${DIM}[%2d/%d] Saga state: %-20s${RESET}" "$i" "$TIMEOUT_SECONDS" "${STATE:-<none>}"

  if [[ "$STATE" == "completed" ]]; then
    echo ""
    ok "Saga reached COMPLETED"
    break
  fi
  sleep 1
done

if [[ "$STATE" != "completed" ]]; then
  echo ""
  fail "Saga did not reach 'completed' within ${TIMEOUT_SECONDS}s (final state: ${STATE:-<none>})"
  echo ""
  echo -e "${BOLD}Order consumer log (last 20 lines):${RESET}"
  tail -n 20 "${CONSUMER_LOG_DIR}/order.log" || true
  echo ""
  echo -e "${BOLD}Payment consumer log (last 20 lines):${RESET}"
  tail -n 20 "${CONSUMER_LOG_DIR}/payment.log" || true
  echo ""
  echo -e "${BOLD}Payout consumer log (last 20 lines):${RESET}"
  tail -n 20 "${CONSUMER_LOG_DIR}/payout.log" || true
  exit 2
fi

# ─────────────────────────────────────────────────────────────
# Verify final state
# ─────────────────────────────────────────────────────────────
step "Verifying final state"

ORDER_STATUS=$(docker compose exec -T order-db psql -U order_user -d order_db -tAc \
  "SELECT status FROM orders WHERE id = '$ORDER_ID';" | tr -d '[:space:]')

PAYMENT_STATUS=$(docker compose exec -T payment-db psql -U payment_user -d payment_db -tAc \
  "SELECT status FROM payments WHERE order_id = '$ORDER_ID';" | tr -d '[:space:]')

PAYOUT_ROW=$(docker compose exec -T payout-db psql -U payout_user -d payout_db -tAc \
  "SELECT status || '|' || retry_count FROM payouts WHERE order_id = '$ORDER_ID';" | tr -d '[:space:]')
PAYOUT_STATUS="${PAYOUT_ROW%%|*}"
PAYOUT_RETRY="${PAYOUT_ROW##*|}"

BALANCE_ROW=$(docker compose exec -T payout-db psql -U payout_user -d payout_db -tAc \
  "SELECT (available->>'amount') || '|' || (reserved->>'amount') || '|' || (debt->>'amount')
   FROM seller_balances WHERE seller_id = 'seller-e2e';" | tr -d '[:space:]')
BAL_AVAILABLE="${BALANCE_ROW%%|*}"
BAL_REST="${BALANCE_ROW#*|}"
BAL_RESERVED="${BAL_REST%%|*}"
BAL_DEBT="${BAL_REST##*|}"

echo ""
printf "  %-20s %s\n" "orders.status"        "$ORDER_STATUS"
printf "  %-20s %s\n" "payments.status"      "$PAYMENT_STATUS"
printf "  %-20s %s\n" "payouts.status"       "$PAYOUT_STATUS"
printf "  %-20s %s\n" "payouts.retry_count"  "$PAYOUT_RETRY"
printf "  %-20s %s\n" "balance.available"    "$BAL_AVAILABLE"
printf "  %-20s %s\n" "balance.reserved"     "$BAL_RESERVED"
printf "  %-20s %s\n" "balance.debt"         "$BAL_DEBT"
printf "  %-20s %s\n" "saga.state"           "$STATE"
echo ""

FAILED=0
[[ "$ORDER_STATUS"   == "completed" ]] || { fail "Expected orders.status=completed, got '$ORDER_STATUS'"; FAILED=1; }
[[ "$PAYMENT_STATUS" == "captured"  ]] || { fail "Expected payments.status=captured, got '$PAYMENT_STATUS'"; FAILED=1; }
[[ "$PAYOUT_STATUS"  == "completed" ]] || { fail "Expected payouts.status=completed, got '$PAYOUT_STATUS'"; FAILED=1; }
[[ "$PAYOUT_RETRY"   == "0"         ]] || { fail "Expected payouts.retry_count=0, got '$PAYOUT_RETRY'"; FAILED=1; }
[[ "$BAL_AVAILABLE"  == "0"         ]] || { fail "Expected balance.available=0, got '$BAL_AVAILABLE'"; FAILED=1; }
[[ "$BAL_RESERVED"   == "0"         ]] || { fail "Expected balance.reserved=0, got '$BAL_RESERVED'"; FAILED=1; }
[[ "$BAL_DEBT"       == "0"         ]] || { fail "Expected balance.debt=0, got '$BAL_DEBT'"; FAILED=1; }
[[ "$STATE"          == "completed" ]] || { fail "Expected saga.state=completed, got '$STATE'"; FAILED=1; }

if [[ $FAILED -ne 0 ]]; then
  exit 3
fi

ok "All assertions passed"

# ─────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}  Happy path succeeded.${RESET}"
echo -e "${DIM}  Order → Payment → Payout → COMPLETED${RESET}"
echo ""