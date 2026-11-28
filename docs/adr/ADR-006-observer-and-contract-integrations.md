# ADR-006: An Observer for Events; the Contract Processor, Sanitizer and ServiceProvider

## Status

Accepted — V2.0.0

## Date

2026-11-28

## Context

ARFA 1.3 P5 asks components to be observable, and the ecosystem wires
components through kariricode/contract ports. The earlier lines did
neither consistently:

- The main line (1.2.3) declared `SanitizationStartedEvent` and
  `SanitizationCompletedEvent` and never emitted them; the September
  2026 tarball emitted them to an optional closure.
- `ProcessorBridge` implemented no contract: in 1.2.3 it returned the
  sanitized array, in the tarball `['data' => …, 'result' => …]`, so the
  same class name meant two output shapes.
- `SanitizerServiceProvider` was a factory (`createRegistry()`,
  `createEngine()`, `createAttributeSanitizer()`) that implemented no
  container contract.

### Prior Art

- kariricode/validator ADR-006: the same observer, a contract
  `Processor` and `Validator`, a contract `ServiceProvider`.
- kariricode/processor-pipeline: steps are contract `Processor`s.
- PSR-14: an event dispatcher is a callable away from a closure.

## Decision

1. **Observer.** `SanitizerEngine` takes an optional `Closure` and calls
   it with a `SanitizationStartedEvent` (field paths, Unix time) and a
   `SanitizationCompletedEvent` (result, duration in milliseconds from
   the monotonic clock) per call. The started event carries names only;
   the completed event carries the result, values included, because an
   audit of changes is its purpose — an observer that logs it decides
   what to redact. A PSR-14 dispatcher plugs in as
   `static fn (object $event) => $dispatcher->dispatch($event)`.
2. **Processor and Sanitizer.** `Integration\SanitizationProcessor`
   implements kariricode/contract's `Processor` and `Sanitizer` for a
   fixed rule map: `process()` and `sanitize()` return the sanitized
   array. Input that is not an array throws `UnsupportedInputException`
   (SAN_3340); a caller that needs the record of changes uses the
   engine.
3. **ServiceProvider.** `SanitizerServiceProvider` implements
   kariricode/contract's `ServiceProvider` and binds, as singletons, the
   registry with the built-in rules, the engine (with the configuration
   and observer the provider was given) and the `AttributeSanitizer`,
   which resolves the container's `PropertyInspector` and
   `PropertyChangeApplier`. A binding of the wrong type throws
   `ConfigurationException` (SAN_3320).

## Consequences

### Positive

- The sanitizer joins a kariricode/processor-pipeline as one step, in
  front of a `ValidationProcessor`, and a framework container as one
  provider, with nothing but kariricode/contract.
- Metrics and audits need no dependency in this package.

### Negative

- **Breaking:** `ProcessorBridge` and the factory methods are gone;
  `SanitizationCompletedEvent` no longer has `$timestamp` (the started
  event has one, and the duration gives the end).
- The observer runs synchronously; a slow observer slows every call.
- An observer that logs `SanitizationCompletedEvent::$result` logs the
  input: redact before writing it anywhere.

## Alternatives Considered

- **Require psr/event-dispatcher:** one more dependency for what a
  closure already does.
- **Return the result from the processor:** the contract `Processor`
  passes values between steps; a result object would force every next
  step to unwrap it.
- **Keep the factory provider:** convenient without a container, and
  `new SanitizerEngine()` already is that convenience.

## References

- ARFA 1.3 P5; kariricode/contract Processor, Sanitizer,
  DI\ServiceProvider.
- PSR-14, Event Dispatcher.
- SPEC-001 §9.
