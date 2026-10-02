# Marketplace Payments

Distributed payment platform for a marketplace, built with Laravel 11, Doctrine ORM, Ecotone, and RabbitMQ. Demonstrates DDD, CQRS, Event-Driven Architecture, and Saga orchestration.

## Architecture

Three services, each with its own PostgreSQL database:

- **Order Service** — Order aggregate, saga orchestration.
- **Payment Service** — Payment processing, refunds.
- **Payout Service** — Seller payouts, balances.

Services communicate via RabbitMQ through a Distributed Bus.

## Stack

- PHP 8.3, Laravel 11
- Doctrine ORM (Data Mapper)
- Ecotone (CQRS, Event Bus, Saga)
- PostgreSQL 16 (one DB per service)
- RabbitMQ 3.13
- Pest (tests), PHPStan level 8, Deptrac, Pint
- Docker Compose

## Status

- ✅ Order Service: Domain, CQRS, Event Bus, Doctrine persistence, 48 tests
- 🚧 Payment Service: pending
- 🚧 Payout Service: pending
- 🚧 RabbitMQ transport: pending
- 🚧 Saga orchestration: pending

## Quick Start

\`\`\`bash
git clone https://github.com/goncharovei/marketplace-payments.git
cd marketplace-payments
cp .env.example .env
# edit .env - set DB and RabbitMQ passwords
docker compose up -d
\`\`\`

Services:
- http://localhost:8001 — Order
- http://localhost:8002 — Payment
- http://localhost:8003 — Payout
- http://localhost:15672 — RabbitMQ UI

## Documentation

See the [Wiki](https://github.com/goncharovei/marketplace-payments/wiki).