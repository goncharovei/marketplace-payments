# Marketplace Payments

A distributed payment system for a marketplace, built with **PHP 8.3**,
**Laravel 11**, **Ecotone CQRS**, **Doctrine ORM**, **RabbitMQ**, and
**PostgreSQL 16**.

The system models the full order fulfilment lifecycle across three
microservices — Order, Payment, and Payout — orchestrated by an Ecotone Saga.
Each service is a separate bounded context, owns its own database, and
communicates with the others exclusively through RabbitMQ via integration
contracts.

> Portfolio project demonstrating production-grade PHP engineering:
> Domain-Driven Design, CQRS, distributed messaging, orchestration Sagas,
> and automated quality gates.

[![CI](https://github.com/goncharovei/marketplace-payments/actions/workflows/ci.yml/badge.svg)](https://github.com/goncharovei/marketplace-payments/actions/workflows/ci.yml)

---

## Table of Contents

- [Architecture](#architecture)
- [Bounded Contexts](#bounded-contexts)
- [Order Fulfilment Saga](#order-fulfilment-saga)
- [Tech Stack & Documentation](#tech-stack--documentation)
- [Running Locally](#running-locally)
- [Testing & Quality Gates](#testing--quality-gates)
- [CI/CD](#cicd)
- [Project Structure](#project-structure)
- [Key Design Decisions](#key-design-decisions)
- [Deliberately Out of Scope](#deliberately-out-of-scope)
- [License](#license)

---

## Architecture

### System Context

Three business services sit behind the Marketplace platform. A buyer places
an order and pays; a seller receives a payout. Each step is a state transition
owned by a different bounded context.

```mermaid
flowchart LR
    Buyer([Buyer])
    Seller([Seller])
    Platform[Marketplace Payments]
    Gateway[(Payment Gateway)]

    Buyer -->|places order<br/>and pays| Platform
    Seller -->|receives payout| Platform
    Platform -->|charges card| Gateway
    Platform -->|transfers to bank| Gateway