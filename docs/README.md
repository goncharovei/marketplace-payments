# Documentation

Complete documentation for the Marketplace Payments project.

> **New here?** Read in order: Architecture → Bounded Contexts → Saga Flow.
> **Just want to run it?** Jump to [Running Locally](running-locally.md).

---

## Contents

### 1. [Architecture](architecture.md)

System-level view of the three services. C4 context and container diagrams,
communication patterns, data ownership boundaries, cross-cutting concerns,
and the layered architecture inside each service.

### 2. [Bounded Contexts](bounded-contexts.md)

Domain model of Order, Payment, and Payout services. Aggregate roots, value
objects, domain events, and how the same concepts differ across contexts.

### 3. [Saga Flow](saga-flow.md)

The `OrderFulfillmentSaga` — an active orchestrator that drives the order
lifecycle across all three services. State machine, happy path sequence,
compensation paths, and manual review.

### 4. [Running Locally](running-locally.md)

Step-by-step setup, migrations, Ecotone consumers, service UIs, E2E scenarios,
troubleshooting, and a catalogue of useful commands.

### 5. [CI/CD](ci-cd.md)

GitHub Actions workflow. Matrix strategy, quality gates (Pest, PHPStan,
Deptrac, Pint), Postgres service container, and branch protection.

### 6. [Design Decisions](decisions.md)

Why Saga orchestration over choreography, why integration contracts live in a
shared kernel, and three architectural improvements deliberately left out of
scope with documented reasoning.

---

## Quick Reference

| Question | Answer |
|---|---|
| How do I run this? | [Running Locally](running-locally.md) |
| How do the services talk to each other? | [Architecture → Communication](architecture.md#communication-patterns) |
| What does the Order aggregate look like? | [Bounded Contexts → Order](bounded-contexts.md#order-service) |
| What happens when a payout fails? | [Saga Flow → Compensations](saga-flow.md#compensation-path) |
| What's checked in CI? | [CI/CD → Gates](ci-cd.md#quality-gates) |
| Why no outbox pattern? | [Design Decisions → Out of Scope](decisions.md#deliberately-out-of-scope) |

---

## Conventions

- Every page has **breadcrumb navigation** at the top and **prev / next**
  links at the bottom.
- Diagrams use [Mermaid](https://mermaid.js.org/) — they render natively on
  GitHub.
- Code blocks are copy-paste ready unless marked otherwise.

---

**Next:** [Architecture →](architecture.md)