# Implementation validation

The inspected starting point is `06cafbc`. The additive implementation is on `codex/image-1.3`. The major release is derived on `codex/image-2.x`. All delivery is local, with no pushes or pull-request operations.

## 1.3 local results

| Environment | Result |
| --- | --- |
| PHP 8.3.35, GD and Imagick | 380 tests, 2,604 assertions |
| PHP 8.4.26, GD | 380 tests, 2,071 assertions, Imagick test skipped |
| PHP 8.5.5, GD and Imagick | 380 tests, 2,604 assertions |
| Lowest dependencies, PHP 8.3 | 380 tests, 2,604 assertions |
| PHPStan 1.12.34 on PHP 8.4 | No errors |
| ECS non-mutating check on PHP 8.3 | No errors |
| Composer strict validation | Passed |
| actionlint 1.7.7 | Passed |
| Local Composer consumer override 1.3.99 | Passed, five real transformations |

The repaired PHP 8.3 runtime works locally. Compatible minimum dependency versions are declared explicitly. The old PHPStan runtime crash and the 13 ECS findings are resolved. The legacy suite still prints Symfony bridge's non-failing “THE ERROR HANDLER HAS CHANGED!” diagnostic at shutdown, with exit status zero. The shared-engine test groups do not produce that diagnostic.

Coverage includes exact legacy filename and JSON fixtures, 54 preserved calculation cases, stream inspection and ownership, unknown adapter options and precedence, copyright and orientation with pixel-level verification for custom metadata readers, SVG/SVGZ, source mutation, pending/completed recovery, exports, revisions and chaining, lifecycle restoration, corrupt records, and a URL-only backend. Process barriers cover cold inspection, duplicate registration, blocking/non-blocking workers, process death, publication-before-completion failure and pruning interrupted during deletion. These tests use independent local processes and shared filesystem storage, not an untested distributed lease implementation.

Linux and Windows PHP 8.3–8.5 matrices, lowest dependency jobs and an Imagick coverage job are configured. Hosted jobs were not run because this task does not push changes. Local checks ran on macOS.

## Warm-cache measurements

Run `php benchmarks/warm-cache.php 200`. The raw 1.3 result is in `benchmark-1.3.json`. This is a local microbenchmark, not a production throughput guarantee. It creates 600×400 raster and SVG sources with a 120px picture configuration and 1x/2x/3x density requests. SVG deduplicates to one target. Latencies depend on the filesystem, PHP extensions and background activity.

| Picture | New median / p95 | Legacy median / p95 | Shared source-lock median |
| --- | --- | --- | --- |
| jpg | 1114.38 / 1521.29 µs | 353.96 / 532.58 µs | 39.17 µs |
| svg | 472.25 / 861.42 µs | 188.46 / 362.21 µs | 40.08 µs |

Both new warm paths perform **zero source-content opens and zero target-content operations**, including existence probes. The content-storage contract has no existence-check operation. Raster pictures perform four shared source-lock acquisitions, four lifecycle reads, four intrinsic-record reads and three target lock/read pairs. SVG pictures perform two shared source locks, two lifecycle reads, two intrinsic reads and one target lock/read pair. There are no warm information-lock acquisitions or record writes.

The new local path has higher latency than the legacy baseline. Lifecycle locking and retained JSON records introduce work that the legacy path does not perform. The lock-only measurements quantify part of that cost. The full difference also includes JSON reads, validation and identity calculation, so it must not be attributed entirely to locks. Legacy source byte counts are not instrumented by this benchmark. Its legacy numbers are end-to-end latency measurements.

Stat-derived versions retain the documented limitations around concurrent in-place edits. Production service adapters, signed URL expiry handling, automatic driver fallback and core-bundle integration remain outside this implementation.

## 2.x local results

The shared consumer runs unchanged using the 2.0.99 local Composer override. The branch requires PHP 8.4 and Symfony Filesystem 7.4.18 or 8.x. Earlier Filesystem releases were excluded because the lowest-dependency run exposed PHP 8.4 deprecations.

| Environment | Result |
| --- | --- |
| PHP 8.4.26, GD | 189 tests, 753 assertions, Imagick test skipped |
| PHP 8.5.5, GD and Imagick | 189 tests, 758 assertions |
| Lowest dependencies, PHP 8.4 | 189 tests, 753 assertions, Imagick test skipped |
| PHPStan 1.12.34 | No errors |
| ECS with Contao configuration 6.14.2 | No errors or tool-runtime deprecations |
| Composer strict validation and actionlint | Passed |
| Local Composer consumer override 2.0.99 | Passed, five real transformations |

The smaller suite removes tests for APIs removed in 2.0. Shared calculations retain 54 golden cases, and six responsive-picture scenarios are ported directly from legacy expectations. Metadata regressions remain. The legacy deprecation extension is removed, and the 2.x suite exits without its error-handler diagnostic. Linux and Windows PHP 8.4–8.5 and lowest-dependency jobs are configured but have not run remotely.

`benchmark-2.x.json` contains the PHP 8.4 measurements. Both raster and SVG warm hits again have zero source-content opens and zero target-content operations. Operation counts match the 1.3 shared engine. Compare the historical 1.3 report for the legacy baseline because the legacy classes no longer exist on 2.x.
