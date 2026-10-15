# Marketplace Payments

A distributed payment system for a marketplace, built with **PHP 8.3**,
**Laravel 11**, **Ecotone CQRS**, **Doctrine ORM**, **RabbitMQ**, and
**PostgreSQL 16**.

The system models the full order fulfilment lifecycle across three
microservices — Order, Payment, and Payout — orchestrated by an Ecotone Saga.
Each service is a separate bounded context, owns its own database, and
communicates with the others exclusively through RabbitMQ via integration
contracts.

[![CI](https://github.com/goncharovei/marketplace-payments/actions/workflows/ci.yml/badge.svg)](https://github.com/goncharovei/marketplace-payments/actions/workflows/ci.yml)

---

## Requirements

- Docker 27+ and Docker Compose v2
- A shell (bash, zsh, or equivalent)

## Quick Start

```bash
git clone https://github.com/goncharovei/marketplace-payments.git
cd marketplace-payments

cp .env.example .env
# Fill in DB_USERNAME, DB_PASSWORD, RABBITMQ_USER, RABBITMQ_PASSWORD

docker compose up -d
docker compose ps   # wait until every service is healthy

for svc in order payment payout; do
  docker compose exec ${svc}-app php artisan migrate --force
  docker compose exec ${svc}-app php artisan doctrine:migrations:migrate --no-interaction
done
```

Then open three terminals and run a consumer per service:

```bash
docker compose exec order-app   php artisan ecotone:run order_service -vvv
docker compose exec payment-app php artisan ecotone:run payment_service -vvv
docker compose exec payout-app  php artisan ecotone:run payout_service -vvv
```

Full setup, E2E scenarios, troubleshooting, and useful commands:
**[Running Locally →](docs/running-locally.md)**

## Service Endpoints

| Service | API | Swagger UI |
|---|---|---|
| Order | http://localhost:8001 | http://localhost:8001/api/documentation |
| Payment | http://localhost:8002 | http://localhost:8002/api/documentation |
| Payout | http://localhost:8003 | http://localhost:8003/api/documentation |
| RabbitMQ Management | http://localhost:15672 | — |

## Tech Stack

| Component | Version | Documentation |
|---|---|---|
| PHP | 8.3 | [php.net](https://www.php.net/manual/en/) |
| Laravel | 11 | [laravel.com/docs/11.x](https://laravel.com/docs/11.x) |
| Ecotone | 1.327 | [docs.ecotone.tech](https://docs.ecotone.tech/) |
| RabbitMQ | 3.13 | [rabbitmq.com/docs](https://www.rabbitmq.com/docs) |
| Doctrine ORM | 3 | [doctrine-project.org](https://www.doctrine-project.org/projects/orm.html) |
| PostgreSQL | 16 | [postgresql.org/docs/16](https://www.postgresql.org/docs/16/) |
| Pest | 3 | [pestphp.com](https://pestphp.com/) |
| PHPStan | 2 | [phpstan.org](https://phpstan.org/) |
| Deptrac | 4 | [github.com/deptrac/deptrac](https://github.com/deptrac/deptrac) |
| Pint | 1 | [laravel.com/docs/11.x/pint](https://laravel.com/docs/11.x/pint) |
| Docker | 27 | [docs.docker.com](https://docs.docker.com/) |

## Documentation

Full documentation lives in **[`docs/`](docs/README.md)** — start there.

| Document | What it covers |
|---|---|
| [Architecture](docs/architecture.md) | C4 model, communication patterns, data ownership, cross-cutting concerns |
| [Bounded Contexts](docs/bounded-contexts.md) | Domain model per service, aggregates, value objects, layered structure |
| [Saga Flow](docs/saga-flow.md) | `OrderFulfillmentSaga` state machine, happy path, compensations |
| [Running Locally](docs/running-locally.md) | Setup, migrations, consumers, E2E scenarios, troubleshooting |
| [CI/CD](docs/ci-cd.md) | GitHub Actions workflow, quality gates, branch protection |
| [Design Decisions](docs/decisions.md) | Why orchestration over choreography, why shared kernel, what was skipped |

## License

MIT — see [LICENSE](LICENSE).