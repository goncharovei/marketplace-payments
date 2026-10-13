# Shared

Cross-service integration contracts for the marketplace-payments project.

## Contents

- `contracts/` — DTOs that cross service boundaries. Consumed via direct
  autoload mapping, no composer install required.

## What belongs here

Only integration contracts — DTO messages sent between services via RabbitMQ.

## What does NOT belong here

- Value Objects (`Money`, `OrderId`, ...) — bound to a specific domain context
- Domain Events — internal to each service
- View models — API read-models, unique per service

See root README for the full architectural reasoning.