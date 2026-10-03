# Persistence and coordination

The default factory owns a dedicated `image-v2` namespace on both release branches. Source information, target records and target content have separate replaceable storage contracts. Backend services are registered by stable identifier, and output-affecting backend configuration is represented by its cache namespace.

The cache root contains:

- `sources/<reference hash>.json` with active version and retained version states
- `information/<reference hash>/<version hash>.json` with schema-versioned format, size, orientation and relative or undefined semantics
- `targets/<reference hash>/<version hash>/<HMAC>/<filename>.json` with pending or completed state, calculated transformation, expected version, backend description and content revision
- `content/` with independently published bytes
- `locks/` with stable source, information and target lock files

Directory grouping supplies target associations without a second target index. Records store descriptions, not generated URLs, secrets, credentials, closures or service objects. Custom adapter descriptions and options must follow the same rule. The library rejects non-serializable option values but cannot identify secrets inside application-defined strings.

Target identity uses secret-based HMAC over source identity and version, intrinsic information, coordinates, effective encoding and copyright options, and backend identifier and namespace. Scheduling, bypass flags, export paths and URL presentation are excluded. Intrinsic information is inspected once under a source-version lock with a second cache read after locking. Invalid intrinsic data is re-inspected. A corrupt target may be rebuilt from a matching complete request. Lifecycle corruption is an explicit error and never discards associations.

## Locking and publication

Normal generation holds a shared source lock. Activation, deletion notification and pruning hold it exclusively. Information and target locks follow the source lock, and export locks are acquired after materialization. A lock is released before changing modes and state is checked again. Unrelated logical sources do not share a lifecycle lock.

JSON and content publication use temporary files and atomic replacement on the same filesystem. Lock files are separate and survive normal pruning. Replacing JSON does not replace lock identity. A worker publishes complete content before marking the record complete. Failure between these writes leaves a retryable pending target. Retries serialize on the same stable target lock and may publish again. Backend deletion must tolerate an already deleted target.

`flock` coordinates independent processes using the same filesystem lock service. It releases on process death. These guarantees depend on filesystem support, particularly for network-mounted storage. Distributed implementations must coordinate all clients and fence both record writes and content publication or deletion when leases can expire. Merely checking lease ownership before writing is insufficient. No distributed lease implementation is included or claimed to have passed these tests.

## Lifecycle

First registration and apparent version changes resolve the authoritative current source under the exclusive lock. Stale requests cannot retire a newer version. Opaque version tokens have no ordering.

`notifyDeleted($reference)` marks retained versions stale and preserves their earliest stale timestamp. It does not query the resolver periodically. `prune($delay)` claims versions whose stale timestamp is at or before the clock-derived cutoff. Use an injected `ClockInterface` for deterministic time behavior.

A request restoring the authoritative version before a pruning claim clears stale state and reuses targets. Once claimed, cleanup must finish before regeneration. Pruning retains the exclusive source lock while deleting each target through its backend and then removing its record. Parent cleanup notifies result-backed source lifecycles before discarding the parent record. Intrinsic information and lifecycle version entries are removed last. Failures retain the claim and remaining records for retry. An ordinary request encountering a claim resumes cleanup before it can activate the source.

Do not delete cache records or lock files concurrently with live workers. A full cache reset is an offline operation after stopping all generators, workers and pruning jobs. A full reset is required when migrating to 2.0.

## Source consistency

Filesystem version tokens hash storage identity, key and stat fingerprint containing device, inode, size, mtime and ctime. Opening validates the path and opened handle. Inspection and snapshotting validate after reading, including the opened handle. The default codec transforms a verified temporary snapshot, then the stored backend checks the expected source version before publishing.

Stat tokens are not cryptographic content versions. Filesystems can have coarse timestamps, and concurrent in-place writes that preserve the fingerprint can evade detection. The library cannot make arbitrary external file writers transactional. Prefer atomic replacement by writers, or immutable and version-addressed storage for stronger guarantees. Remote adapters must bind transformations to the expected source version rather than following a mutable asset reference. Custom codecs must uphold the snapshot contract.

Memory use is bounded by incremental buffering into temporary streams, not a promised fixed header size. Disk space and full-image codec memory remain workload-dependent. All streams owned by the library are closed on success and failure. Decoders should be treated as processing untrusted media according to the application's own resource limits.
