[← Documentation Index](README.md) · [← Saga Flow](saga-flow.md)

# Running Locally

Complete guide to setting up and running the Marketplace Payments platform
on your machine. Covers the happy path, the compensation path, service UIs,
troubleshooting, and a catalogue of useful commands.

---

## Requirements

| Tool | Minimum version | Check |
|---|---|---|
| Docker | 27.0 | `docker --version` |
| Docker Compose | v2.20 | `docker compose version` |
| Git | 2.40 | `git --version` |
| A shell | bash, zsh, or equivalent | — |

Optional but useful:

- `jq` — for formatting JSON responses from the APIs
- `psql` — for connecting to the databases directly without going through
  `docker compose exec`

---

## Setup

### 1. Clone the repository

```bash
git clone https://github.com/goncharovei/marketplace-payments.git
cd marketplace-payments
```

### 2. Create the environment file

```bash
cp .env.example .env
```

Open `.env` and fill in the values. The template references several
variables that must be set before `docker compose up`:

```dotenv
ORDER_DB_DATABASE=order_db
ORDER_DB_USERNAME=order_user
ORDER_DB_PASSWORD=<choose-a-password>

PAYMENT_DB_DATABASE=payment_db
PAYMENT_DB_USERNAME=payment_user
PAYMENT_DB_PASSWORD=<choose-a-password>

PAYOUT_DB_DATABASE=payout_db
PAYOUT_DB_USERNAME=payout_user
PAYOUT_DB_PASSWORD=<choose-a-password>

RABBITMQ_USER=<choose-a-user>
RABBITMQ_PASSWORD=<choose-a-password>
```

These credentials are used both by the PostgreSQL containers and by the
Laravel applications. They must match.

### 3. Start the stack

```bash
docker compose up -d
```

The first run builds the shared PHP image — this takes 1–2 minutes. After
that, container startup is near-instant.

Wait until every service reports `healthy`:

```bash
docker compose ps
```

Expected output (only the `State` column matters):

```
NAME                STATUS
mp-order-app        Up (healthy)
mp-order-db         Up (healthy)
mp-order-web        Up
mp-payment-app      Up (healthy)
mp-payment-db       Up (healthy)
mp-payment-web      Up
mp-payout-app       Up (healthy)
mp-payout-db        Up (healthy)
mp-payout-web       Up
mp-rabbitmq         Up (healthy)
```

If a `*-db` container is stuck in `(health: starting)` for more than
30 seconds, check its logs:

```bash
docker compose logs order-db
```

### 4. Run migrations

Each service has two independent migration sets:

- **Laravel migrations** — framework tables (`cache`, `jobs`, `sessions`,
  `users`). Run with `php artisan migrate`.
- **Doctrine migrations** — aggregate tables (`orders`, `payments`,
  `payouts`, `seller_balances`). Run with `php artisan doctrine:migrations:migrate`.

Run both for all three services:

```bash
for svc in order payment payout; do
  echo "=== ${svc} ==="
  docker compose exec ${svc}-app php artisan migrate --force
  docker compose exec ${svc}-app php artisan doctrine:migrations:migrate --no-interaction
done
```

The `--force` flag skips the production-environment confirmation prompt.
The `--no-interaction` flag does the same for Doctrine migrations.

### 5. Start Ecotone consumers

Each service needs a long-running consumer process to handle asynchronous
messages. This process is **not** started by `docker compose up` — it must
be running separately.

Open **three terminals** (one per service) and leave them running:

**Terminal 1 — Order Service**

```bash
docker compose exec order-app php artisan ecotone:run order_service -vvv
```

**Terminal 2 — Payment Service**

```bash
docker compose exec payment-app php artisan ecotone:run payment_service -vvv
```

**Terminal 3 — Payout Service**

```bash
docker compose exec payout-app php artisan ecotone:run payout_service -vvv
```

The `-vvv` flag increases log verbosity. Without a running consumer, the
service silently accumulates messages in RabbitMQ and nothing progresses.

---

## Service UIs

| Service | API base URL | Swagger UI | RabbitMQ UI |
|---|---|---|---|
| Order | http://localhost:8001 | http://localhost:8001/api/documentation | — |
| Payment | http://localhost:8002 | http://localhost:8002/api/documentation | — |
| Payout | http://localhost:8003 | http://localhost:8003/api/documentation | — |
| RabbitMQ Management | — | — | http://localhost:15672 |

RabbitMQ Management credentials are the `RABBITMQ_USER` and
`RABBITMQ_PASSWORD` values from `.env`.

**Swagger UI** — interactive API explorer. Every endpoint documented, every
request executable from the browser. Recommended for exploring the API
before writing client code.

**RabbitMQ Management** — shows exchanges, queues, message rates. Useful for
verifying that messages are being published and consumed. The relevant queue
is `distributed_order_service` (or `_payment_service`, `_payout_service`).

---

## E2E Happy Path

The happy path — order placement to completion — can be exercised either
manually (see below) or through the automated smoke-test script.

### Automated test

The repository ships a self-contained script that:

1. Verifies the Docker stack is running.
2. Optionally cleans RabbitMQ queues, Ecotone state, and business data.
3. Starts the three Ecotone consumers in the background.
4. Places an order and starts payment.
5. Polls the saga until it reaches `completed` (30-second timeout).
6. Asserts the final state of `orders`, `payments`, `payouts`, and
   `seller_balances`.
7. Stops the consumers and reports pass / fail.

Run it from the project root:

```bash
./scripts/e2e-happy-path.sh
```

Flags:

| Flag | Effect |
|---|---|
| *(none)* | Clean state, run, stop consumers |
| `--no-clean` | Skip the cleanup step — use when running twice in a row |
| `--keep-runn` | Leave consumers running after the script exits (for debugging) |
| `-h`, `--help` | Print usage |

Exit codes:

| Code | Meaning |
|---|---|
| `0` | Happy path succeeded |
| `1` | A prerequisite container is not running |
| `2` | Saga did not reach `completed` within the timeout |
| `3` | Final-state assertion failed |

Example — a successful run ends with:

```
  orders.status        completed
  payments.status      captured
  payouts.status       completed
  payouts.retry_count  0
  balance.available    0
  balance.reserved     0
  balance.debt         0
  saga.state           completed

  ✓ All assertions passed

  Happy path succeeded.
  Order → Payment → Payout → COMPLETED
```

The script is safe to run in CI as an end-to-end smoke test — the exit
codes are deterministic and consumer output is captured to a temporary
directory (removed on exit unless `--keep-runn` is set).

### Manual walkthrough

If you want to run the flow step by step and observe each state, follow the
steps below.

### Step 1 — Place an order

```bash
ORDER_ID=$(docker compose exec -T order-app php artisan tinker --execute="
\$bus = app(\Ecotone\Modelling\CommandBus::class);
echo \$bus->send(new \App\Application\Command\PlaceOrderCommand(
    buyerId: 'buyer-1',
    sellerId: 'seller-1',
    items: [['productId' => 'p1', 'quantity' => 1, 'amount' => 2500, 'currency' => 'RUB']],
))->toString();
" | tail -n 1)

echo "Order ID: $ORDER_ID"
```

At this point, the Order Service emits `OrderCreated`, the saga transitions
to `STARTED`, and `order.created` is published to RabbitMQ. The Payment
Service's `OrderCreatedConsumer` picks it up and initiates a payment.

### Step 2 — Start payment

```bash
docker compose exec -T order-app php artisan tinker --execute="
app(\Ecotone\Modelling\CommandBus::class)->send(
    new \App\Application\Command\StartPaymentCommand('$ORDER_ID')
);
echo 'Payment started' . PHP_EOL;
"
```

This moves the order to `PaymentProcessing`, emits `PaymentStarted`, and
sends `payment.capture` to Payment Service. Payment then authorizes and
captures, emitting `payment.completed` back to Order.

### Step 3 — Wait for completion

The Saga will drive the rest automatically. Poll the order status:

```bash
for i in $(seq 1 30); do
  STATE=$(docker compose exec -T order-db psql -U order_user -d order_db -tAc \
    "SELECT document->>'state' FROM ecotone_document_store
     WHERE document->>'orderId' = '$ORDER_ID';")
  echo "[$i/30] Saga state: $STATE"
  if [ "$STATE" = "completed" ]; then
    echo "Order completed."
    break
  fi
  sleep 1
done
```

Expected output ends with:

```
Saga state: completed
Order completed.
```

### Step 4 — Verify end state

```bash
echo "--- ORDER ---"
docker compose exec order-db psql -U order_user -d order_db \
  -c "SELECT id, status FROM orders WHERE id = '$ORDER_ID';"

echo "--- PAYMENT ---"
docker compose exec payment-db psql -U payment_user -d payment_db \
  -c "SELECT id, status FROM payments WHERE order_id = '$ORDER_ID';"

echo "--- PAYOUT ---"
docker compose exec payout-db psql -U payout_user -d payout_db \
  -c "SELECT id, status, retry_count FROM payouts WHERE order_id = '$ORDER_ID';"

echo "--- SELLER BALANCE ---"
docker compose exec payout-db psql -U payout_user -d payout_db \
  -c "SELECT available, reserved, debt FROM seller_balances WHERE seller_id = 'seller-1';"
```

Expected values:

| Table | Field | Value |
|---|---|---|
| `orders` | `status` | `completed` |
| `payments` | `status` | `captured` |
| `payouts` | `status` | `completed` |
| `payouts` | `retry_count` | `0` |
| `seller_balances` | `available` | `{amount: 0, currency: RUB}` |
| `seller_balances` | `reserved` | `{amount: 0, currency: RUB}` |
| `seller_balances` | `debt` | `{amount: 0, currency: RUB}` |

### Via REST API

The same flow can be exercised via HTTP:

```bash
curl -s -X POST http://localhost:8001/api/v1/orders \
  -H 'Content-Type: application/json' \
  -d '{
    "buyerId": "buyer-1",
    "sellerId": "seller-1",
    "items": [{"productId": "p1", "quantity": 1, "amount": 2500, "currency": "RUB"}]
  }' | jq

# Then:
curl -s -X POST http://localhost:8001/api/v1/orders/$ORDER_ID/pay | jq
curl -s http://localhost:8001/api/v1/orders/$ORDER_ID | jq
```

---

## Compensation Path

The compensation flow — payout failure leading to `PAYOUT_FAILED` — is
covered comprehensively by Feature tests in all three services:

- `Payout Service` — retry exhaustion, escalation, `SellerBalance.debt` increment
- `Order Service` — `PayoutFailedConsumer`, saga transition to `PAYOUT_FAILED`

An end-to-end run of this scenario against the running Docker stack is
**deliberately not supported** because arming `FakePayoutGateway` to fail
requires cross-process state — the tinker session and the Ecotone consumer
are separate PHP processes with no shared memory. Adding a file-based or
DB-based side channel for a demo would be a test-double hack, not a
production pattern.

Run the Feature tests to exercise this path:

```bash
docker compose exec payout-app ./vendor/bin/pest --filter=CreatePayoutCommandTest
docker compose exec payout-app ./vendor/bin/pest --filter=PayoutEscalatedNotifierTest
docker compose exec order-app  ./vendor/bin/pest --filter=PayoutFailedConsumerTest
```

See [Design Decisions → Out of Scope](decisions.md#deliberately-out-of-scope)
for the reasoning.

---

## Useful Commands

### Container lifecycle

```bash
# Start everything
docker compose up -d

# Stop everything (keeps volumes and data)
docker compose down

# Stop and wipe all data — databases, RabbitMQ, cached state
docker compose down -v

# Recreate containers without rebuilding images
docker compose down && docker compose up -d --force-recreate

# Rebuild PHP image from scratch
docker compose build --no-cache order-app payment-app payout-app

# Restart a single service
docker compose restart order-app
```

### Logs

```bash
# Follow logs of one service
docker compose logs -f order-app

# Follow logs of all services
docker compose logs -f

# Tail last 100 lines of payment-db
docker compose logs --tail=100 payment-db

# Show logs since a specific time
docker compose logs --since=10m order-app
```

### Shell access

```bash
# Bash inside an app container
docker compose exec order-app bash

# Laravel tinker (interactive REPL)
docker compose exec order-app php artisan tinker

# PostgreSQL client inside a database container
docker compose exec order-db psql -U order_user -d order_db
```

### Database operations

```bash
# Connect to a database
docker compose exec order-db psql -U order_user -d order_db

# Inspect a table
docker compose exec order-db psql -U order_user -d order_db -c "\d orders"

# Dump a database to a file
docker compose exec order-db pg_dump -U order_user order_db > order_backup.sql

# Restore
cat order_backup.sql | docker compose exec -T order-db psql -U order_user -d order_db

# Wipe business data (keep migrations)
docker compose exec order-db psql -U order_user -d order_db -c "TRUNCATE orders CASCADE;"
```

### RabbitMQ operations

```bash
# List queues with message counts
docker compose exec rabbitmq rabbitmqctl list_queues name messages

# List exchanges
docker compose exec rabbitmq rabbitmqctl list_exchanges name type

# Purge a single queue
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_order_service

# Purge all three service queues
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_order_service
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_payment_service
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_payout_service
```

### Ecotone state reset

During local development, stale saga state can cause confusing behaviour.
Reset it with:

```bash
docker compose exec order-app php artisan tinker --execute="
DB::table('ecotone_deduplication')->truncate();
DB::table('ecotone_document_store')->truncate();
"
```

Run the same on `payment-app` and `payout-app` if their deduplication tables
are involved. `ecotone_document_store` is only used by Order Service (Saga
state).

### Quality tools

```bash
# Run all four tools per service
for svc in order payment payout; do
  echo "=== ${svc} ==="
  docker compose exec ${svc}-app ./vendor/bin/pest
  docker compose exec ${svc}-app ./vendor/bin/phpstan analyse --no-progress
  docker compose exec ${svc}-app ./vendor/bin/deptrac analyse
  docker compose exec ${svc}-app ./vendor/bin/pint --test \
    app/Domain app/Application app/Infrastructure app/Presentation tests
done
```

---

## Troubleshooting

### A service is stuck in `(health: starting)`

Postgres containers occasionally take longer than expected on first boot,
especially on machines with slow disk I/O. Wait 30 more seconds. If still
unhealthy:

```bash
docker compose logs order-db
```

Common cause: `.env` has empty `ORDER_DB_PASSWORD` or a typo. The container
exits with `POSTGRES_PASSWORD not specified` in that case.

### `doctrine:migrations:migrate` says "No migrations found"

The Doctrine migrations directory is `services/<name>/src/database/doctrine-migrations`.
If it's empty, the container was likely built without the migrations copied
in. Check inside the container:

```bash
docker compose exec order-app ls database/doctrine-migrations/
```

If empty, verify that the directory exists in your local checkout.

### Messages accumulate in RabbitMQ, nothing happens

The Ecotone consumer is not running. Check RabbitMQ:

```bash
docker compose exec rabbitmq rabbitmqctl list_queues name messages
```

If a queue shows non-zero messages and a growing number, no consumer is
draining it. Start the consumer for that service (see [Step 5](#5-start-ecotone-consumers)).

### Order stays in `payment_processing` forever

Check that both Order and Payment consumers are running. The flow requires:

1. Order publishes `payment.capture` — requires the Order consumer.
2. Payment receives it, captures, publishes `payment.completed` — requires
   the Payment consumer.
3. Order receives `payment.completed`, marks the order paid — requires the
   Order consumer again.

Missing any one of these breaks the chain silently.

### Port already in use

If port `8001`, `8002`, `8003`, or `15672` is taken, change the mapping in
`docker-compose.yml`:

```yaml
  order-web:
    ports:
      - "9001:80"    # was 8001
```

Then `docker compose up -d`.

### `Class "Shared\Contracts\..." not found`

The autoload mapping for shared contracts is missing. Verify the mapping
exists:

```bash
docker compose exec order-app grep Shared vendor/composer/autoload_psr4.php
```

Expected:

```php
'Shared\\Contracts\\' => array($baseDir . '/../../../shared/contracts/src'),
```

If missing, run:

```bash
docker compose exec order-app composer dump-autoload
```

Same for payment and payout. Also verify that the volume
`./shared:/shared:ro` is mounted in `docker-compose.yml` and visible inside
the container:

```bash
docker compose exec order-app ls -la /shared/contracts/src/
```

### Wrong state in the saga

If an order is stuck in an unexpected state (e.g., `payout_failed` when you
expected `completed`), reset the saga state and try again:

```bash
# Clear saga state
docker compose exec order-app php artisan tinker --execute="
DB::table('ecotone_document_store')->truncate();
DB::table('ecotone_deduplication')->truncate();
"

# Clear business data
docker compose exec order-db   psql -U order_user   -d order_db   -c "TRUNCATE orders CASCADE;"
docker compose exec payment-db psql -U payment_user -d payment_db -c "TRUNCATE payments CASCADE;"
docker compose exec payout-db  psql -U payout_user  -d payout_db  -c "TRUNCATE payouts, seller_balances CASCADE;"

# Clear queues
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_order_service
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_payment_service
docker compose exec rabbitmq rabbitmqctl purge_queue distributed_payout_service
```

Now start a fresh order.

### Rebuild from scratch

If nothing else works:

```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
# Wait for healthy, then re-run migrations
```

`down -v` deletes named volumes (`order-db-data`, `payment-db-data`,
`payout-db-data`, `rabbitmq-data`). All data is lost. Migrations recreate the
schema; no business data is restored.

---

## References

- **Docker Compose reference** — [docs.docker.com/compose](https://docs.docker.com/compose/)
- **Laravel Artisan** — [laravel.com/docs/11.x/artisan](https://laravel.com/docs/11.x/artisan)
- **Doctrine Migrations** — [doctrine-project.org/projects/migrations](https://www.doctrine-project.org/projects/migrations.html)
- **PostgreSQL 16 documentation** — [postgresql.org/docs/16](https://www.postgresql.org/docs/16/)
- **RabbitMQ CLI tools** — [rabbitmq.com/cli.html](https://www.rabbitmq.com/cli.html)

---

**Continue reading:**
[← Saga Flow](saga-flow.md) · [Documentation Index](README.md) · [CI/CD →](ci-cd.md)