[← Documentation Index](README.md) · [← CI/CD](ci-cd.md)

# Design Decisions

Every architectural choice has a cost. This document explains the trade-offs
behind the major decisions in Marketplace Payments — what was chosen, what
was rejected, and why.

The first three sections cover decisions baked into the current
implementation. The last section documents three improvements that were
**evaluated and consciously skipped**, with the reasoning that led to each.

---

## Saga orchestration, not choreography

A long-running business process spanning multiple services can be modelled
in two ways.

**Choreography.** Each service listens for events and reacts. There is no
central coordinator. The process is implicit in the wiring.

**Orchestration.** A central saga issues commands, tracks state, and decides
what happens next. The process is visible in one place.

We chose **orchestration** — specifically, a single `OrderFulfillmentSaga`
living in Order Service, persisted in `ecotone_document_store`.

### Why

**One place knows the whole process.** Reading a single file
(`OrderFulfillmentSaga.php`) reveals the full order lifecycle: what triggers
each transition, what commands are issued, what compensations exist. In a
choreographed system, the same understanding requires reading handlers across
three services and reconstructing the flow mentally.

**Compensations are easier to reason about.** When a payout fails, the Saga
knows the order was paid. It can decide — in code — that the buyer stays
charged, the seller carries the debt, and the case goes to manual review.
In choreography, that decision would be distributed across services and
implicit in their reactions.

**State is queryable.** The Saga's current state lives in a database table.
Answering "where is this order right now?" is one SQL query. Debugging a
stuck order takes seconds, not a session of log correlation.

**Testing is straightforward.** A saga can be unit-tested by feeding it
events and asserting on transitions. Choreography requires integration tests
to exercise the same flow.

### The cost

The orchestrator becomes a coupling point. Every service that wants to
participate in the process must know about the saga's contracts. If we later
extract the saga into its own service, every consumer's routing must change.

This is acceptable here because **Order is the natural entry point** of the
platform. The saga's home (Order Service) is where orders are placed; there
is no more natural alternative.

### When to reconsider

If the saga grows to coordinate dozens of services, or if the coordination
logic becomes so complex it deserves its own bounded context, extract it into
a dedicated orchestrator service. Until then, keeping it in Order Service
avoids a network hop.

### References

- **Saga pattern** — [microservices.io/patterns/data/saga.html](https://microservices.io/patterns/data/saga.html)
- **Hector Garcia-Molina, Kenneth Salem, *Sagas* (1987)** — the original paper
- **Implementing Domain-Driven Design** — Vaughn Vernon (2013), Chapter 8: *Domain Events*

---

## Integration contracts in the shared kernel

Six DTOs cross service boundaries:

- `OrderCreatedMessage`
- `CapturePaymentMessage`
- `PaymentCompletedMessage`
- `CreatePayoutMessage`
- `PayoutCompletedMessage`
- `PayoutFailedMessage`

All six live in `shared/contracts/`, consumed by each service via a direct
autoload mapping (no composer path repository, no `composer update` after
every edit).

### Why a shared package at all

These DTOs are the **public contracts** between services. If Payment Service
expects `OrderCreatedMessage.orderId` to be a string, and Order Service
publishes it as a string, both sides must agree. Duplicating the class in
each service invites drift — a field renamed in one place and not in another
causes silent deserialization failures that only surface at runtime.

One class, one source of truth.

### Why not a composer path repository

We considered declaring `marketplace/contracts` as a path repository in each
service's `composer.json`. We rejected this because:

- Every edit to a shared class would require `composer update marketplace/contracts`
  in three services. Forget once, and the container has stale code.
- `composer.lock` would churn on every contract change.
- CI caching becomes fragile — cache keys change on every shared edit.

Instead, each service's `composer.json` has:

```json
"autoload": {
    "psr-4": {
        "Shared\\Contracts\\": "../../../shared/contracts/src/"
    }
}
```

Composer resolves the namespace to a filesystem path. No install step, no
lock churn, no cache invalidation. Editing a shared DTO is instant.

See [Architecture → Communication Patterns](architecture.md#communication-patterns)
for how these DTOs are routed.

### What does NOT go into the shared kernel

- **Value Objects** (`Money`, `OrderId`, `BuyerId`) — they look identical
  across services, but carry different semantics. See
  [3. Shared kernel for domain code](#3-shared-kernel-for-domain-code).
- **Domain Events** (`OrderPaid`, `PaymentCaptured`) — internal to each
  service. They never cross service boundaries.
- **View models** (`OrderView`, `PaymentView`) — API read models, unique
  per service, shaped by each service's HTTP contract.

**Rule of thumb:** integration contracts go into the shared kernel; domain
concepts stay in their bounded context.

---

## Every service owns its database

Three services, three PostgreSQL databases: `order_db`, `payment_db`,
`payout_db`. There are **no cross-service joins** and **no shared tables**.

### Why

**Bounded contexts are only real if they own their data.** If Payment Service
could join `payments` against `orders`, it would slowly grow implicit
dependencies on Order Service's schema — a `SELECT ... JOIN orders ...` in
the payment code, then a query touching `order_items`, then a report that
aggregates across both. Within a year the "two services" would be one
logically coupled system with network overhead.

Preventing cross-service joins forces integration to go through explicit,
versionable contracts: messages and APIs. This is the whole point of
microservices.

**Schemas can evolve independently.** Order Service can restructure its
`orders` table without coordinating with Payment Service. Payment Service
can index its `payments` table differently. No shared migration.

**Failures are isolated.** A lock or slow query in `order_db` cannot block
Payment Service. This is not a theoretical benefit — in shared databases,
one bad query can take down every service that uses that database.

### The cost

**No eventual consistency queries across services.** If you want to show a
user "their order + its payment + its payout" in a single screen, you cannot
do a three-table join. You either:

- Compose the view in an API gateway that calls three services.
- Build a read-model projection that aggregates events into a denormalized
  table.

Neither is free. Neither is impossible. But you must decide per use case.

**Cross-service references are opaque.** `payment.order_id` is a UUID. It is
not a foreign key. There is no referential integrity between services. If
Order Service deletes an order (which it does not), Payment Service would
not know.

This is the price of boundaries. See
[Bounded Contexts → Shared Concepts](bounded-contexts.md#shared-concepts) for
how the same identifier means different things in different contexts.

---

## Why no event sourcing

Domain events are recorded in memory (`Order::recordEvent()`) and published
after the repository flushes the aggregate. We do **not** persist an event
stream. The aggregates in `orders`, `payments`, `payouts` are the source of
truth.

### Why

Event sourcing is a powerful pattern for domains that need:

- **Temporal queries** — "what did this aggregate look like on date X?"
- **Full audit trails** — every state change is preserved.
- **Retroactive projections** — replay events to build new read models.

We have none of these requirements. Our queries are about *current* state
("where is this order?"). Our audit trail is the RabbitMQ log and the domain
events passing through it. Our read models are constructed from live
aggregates, not from event replays.

**Event sourcing adds complexity.** Snapshots, projections, upcasting, event
versioning, replay infrastructure. For a project that does not need temporal
queries, this is pure overhead.

### What we do instead

Domain events are:

1. Recorded inside the aggregate (in-memory buffer).
2. Released by the repository after `flush()`.
3. Published via Ecotone's `EventBus`.

The aggregate's persisted state is the authoritative record. Events are a
**communication mechanism**, not the source of truth.

This is the standard approach in DDD without event sourcing — sufficient for
the vast majority of business domains.

### References

- **Event Sourcing** — Martin Fowler — [martinfowler.com/eaaDev/EventSourcing.html](https://martinfowler.com/eaaDev/EventSourcing.html)
- **When not to use event sourcing** — Greg Young's talks (paraphrased in
  most DDD literature)

---

## Deliberately Out of Scope

Three architectural improvements were evaluated and **consciously skipped**.
Each is documented so the reasoning is visible: what the improvement would
add, what it would cost, and the conditions under which it should be
revisited.

### 1. Outbox pattern

Ecotone 1.327 supports outbox via a DB-backed message channel
(`DbalBackedMessageChannelBuilder`). We considered enabling it. We skipped it.

**What it solves.** The current code writes domain events to the same
transaction as the aggregate (via `DoctrineOrderRepository::save()`), so
events are consistent with data **at commit time**. The only remaining risk
is a crash in the microsecond window between `flush()` and `publish()` — the
aggregate is committed, but the message never reaches RabbitMQ.

**Why we skipped it.**

- The crash window is measured in microseconds and only matters at high write
  volumes with frequent failures.
- Enabling outbox requires a DB-backed message channel, a background
  publisher process, and a new table. All three add operational surface.
- The failure mode is not silent: if a message is lost, downstream services
  simply never receive it. There is no data corruption — only a stuck order.
  Manual intervention or a redelivery job fixes it.
- For a demo project, this is not a meaningful risk. For a production system
  at scale, it becomes one.

**When to revisit.** When (a) throughput requires asynchronous publication,
or (b) the system runs with enough concurrent writers that the crash window
becomes statistically relevant — say, tens of thousands of events per second.

**References.**

- **Ecotone Outbox** — [docs.ecotone.tech/modelling/dbal/outbox](https://docs.ecotone.tech/modelling/dbal/outbox)
- **Transactional Outbox** — Chris Richardson — [microservices.io/patterns/data/transactional-outbox.html](https://microservices.io/patterns/data/transactional-outbox.html)

---

### 2. Isolated test database

CI runs against a dedicated PostgreSQL service container. Locally, Feature
tests are wrapped in a transaction and rolled back after each test. We
considered adding a second `_test` database per service. We skipped it.

**What it would solve.** Local Feature tests would run against a fully
isolated database, physically separate from the dev database. A test that
intentionally commits (bypassing the transaction wrapper) would not pollute
dev data.

**Why we skipped it.**

- CI already has full isolation — a fresh Postgres container per job.
- Locally, `Pest.php` wraps every test in a transaction and rolls back in
  `afterEach`. No test writes are visible after the suite runs.
- Adding `_test` databases requires init scripts in three Postgres
  containers, an extra `.env.testing` file per service, and duplicate
  migrations. Non-trivial setup cost for a benefit that the transaction
  wrapper already delivers.
- The one scenario it would help — a test that commits mid-run — is a signal
  the test itself is designed wrong. Fixing that test is cheaper than adding
  infrastructure.

**When to revisit.** When (a) parallel Pest tests are enabled, so transaction
wrappers no longer suffice (transactions are per-connection, not per-thread);
or (b) a test legitimately needs to commit and observe the committed state.

**References.**

- **Pest parallel testing** — [pestphp.com/docs/parallel-testing](https://pestphp.com/docs/parallel-testing)
- **Database transactions in tests** — [laravel.com/docs/11.x/database-testing#database-transactions](https://laravel.com/docs/11.x/database-testing#database-transactions)

---

### 3. Shared kernel for domain code

We considered putting `Money`, `OrderId`, `DomainEvent`, and marker
interfaces into `shared/`. We deliberately kept them per-service.

**What it would solve.** Six small value objects and two interfaces are
duplicated across three services. Extracting them would eliminate the
duplication and ensure that, for example, a bug fix in `Money::subtract()`
propagates everywhere.

**Why we skipped it.**

Value Objects in different bounded contexts look identical but carry
**different semantics**:

- `OrderId` in Order Service is the aggregate's identity. It is generated
  by `Order::place()` and serves as the primary key.
- `OrderId` in Payment Service is a reference to an aggregate owned by
  another service. It is received in a message; Payment Service never loads
  the aggregate it points to.
- `Money` in Order Service is a total order amount.
- `Money` in Payment Service is a charge and a cumulative refunded amount.
- `Money` in Payout Service is a net payout and a platform fee.

The code is identical today. The **meaning** is not. Coupling them via a
shared kernel means that tomorrow, when Payment decides to validate `OrderId`
against a specific format and Order decides not to, the classes must diverge
in a shared codebase — either through feature flags, subclassing, or splitting
the shared kernel back into per-service copies. All three are worse than
starting with separate classes.

Extracting domain concepts into a shared kernel also **breaks Domain layer
isolation**, which is enforced by Deptrac. The Domain layer would depend on
`Shared\Contracts\*`, and the whole point of Domain purity is lost.

**When to revisit.** Never, in this architecture. If domain models truly
converge to the point where they must share code, that is a signal the
contexts should **merge** into a single service — not that the domain should
be shared across three.

**References.**

- **Bounded Context** — Martin Fowler — [martinfowler.com/bliki/BoundedContext.html](https://martinfowler.com/bliki/BoundedContext.html)
- **Shared Kernel** — Eric Evans, *Domain-Driven Design* (2003), Chapter 14
- **Implementing Domain-Driven Design** — Vaughn Vernon (2013), Chapter 2

---

## Summary Table

| Decision | Choice | Revisit when |
|---|---|---|
| Orchestration vs choreography | Orchestration — `OrderFulfillmentSaga` | Saga grows to dozens of services |
| Integration contracts | Shared kernel, direct autoload mapping | Never — this is the correct pattern |
| Data ownership | One database per service | Never — this is the correct pattern |
| Event sourcing | Not used — aggregates are source of truth | Temporal queries become a requirement |
| Outbox pattern | Skipped | Throughput requires async publication |
| Isolated test database | Skipped — transactions suffice | Parallel tests are enabled |
| Shared domain kernel | Skipped — bounded contexts prevail | Contexts merge (but then don't share) |

---

## References

- **Domain-Driven Design** — Eric Evans, *Tackling Complexity in the Heart of Software* (2003)
- **Implementing Domain-Driven Design** — Vaughn Vernon (2013)
- **Building Microservices** (2nd ed.) — Sam Newman (2021)
- **Microservices Patterns** — Chris Richardson (2018)
- **martinfowler.com** — [Bounded Context](https://martinfowler.com/bliki/BoundedContext.html), [CQRS](https://martinfowler.com/bliki/CQRS.html), [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)
- **microservices.io** — [Saga](https://microservices.io/patterns/data/saga.html), [Transactional Outbox](https://microservices.io/patterns/data/transactional-outbox.html)

---

**Continue reading:**
[← CI/CD](ci-cd.md) · [Documentation Index](README.md)