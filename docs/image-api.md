# Image API available in 1.3 and 2.0

The new engine is additive in 1.3 and is the only engine in 2.0. Its public contracts live in `Contao\Image`. It accepts a source stream and returns a target identity, dimensions, output format and URL. Filesystem paths and readable streams are optional capabilities.

```php
$configuration = new FilesystemImageConfiguration($cacheDirectory, '/images', $secret);
$resolver = new FilesystemSourceResolver(['uploads' => $uploadDirectory]);
$codec = new ImagineImageDecoderEncoder(new Imagine\Gd\Imagine());
$factory = new FilesystemImageFactory($configuration, $resolver, $codec);
$source = $resolver->resolve(new SourceReference('uploads', 'photo.jpg'));
$result = $factory->getResizer()->resize(
    $source,
    (new ResizeConfiguration())->withWidth(640),
    (new ProcessingOptions())->withEncoding((new EncodingOptions())->withQuality(85))
);
echo $result->getUrl();
```

The URL prefix must serve the factory's `<cacheDirectory>/image-v2/content` directory. Keep the secret stable and private. A changed secret or output-affecting backend namespace creates different target identities. A changed URL prefix does not. Change `withNamespace()` when replacing a codec, changing its driver or changing output-affecting backend configuration.

`SourceReference` identifies a storage and key. `SourceVersion` adds an opaque version token. Neither stores credentials or resources. Resolve credentials through your application services. Sources open a fresh readable stream for each `openStream()` call and transfer ownership to the caller. The codec similarly transfers ownership of its output stream. Close streams in `finally` blocks.

`ProcessingOptions`, `EncodingOptions` and `with…()` configuration methods return clones. Picture configurations deep-clone nested items and resize configurations. Entering the engine snapshots geometry and options so legacy setters cannot alter deferred work. Proportional mode remains supported. Its previous removal announcement is superseded, although the legacy 1.3 setter still emits its historical deprecation.

Common encoding values override equivalent values in the `imagine` adapter bag. Format is detected from source bytes when unspecified. Quality accepts 0–100, PNG compression 0–9, and explicit PNG compression overrides quality-derived compression. Interlacing accepts `none`, `line`, `plane` and `partition`. Resampling accepts `auto`, `nearest`, `box`, `linear`, `cubic` and `lanczos`. Unspecified values preserve encoder defaults, including the established WebP quality of 80. GD and SVG retain their native resampling behavior. Unknown serializable adapter options are retained. Associative keys are sorted for identity calculation and lists retain their order.

Copyright metadata preservation defaults match the legacy engine. Use `withPreserveCopyrightMetadata()` to replace the allowed keys or disable preservation with an empty array. Metadata is read during transformation from the same verified snapshot as image geometry and encoding. Intrinsic records never cache copyright metadata.

## Deferred processing and workers

Set `withDeferred(true)` to register pending work and obtain dimensions and a URL without encoding. A backend may return an already complete prepared reference, as a URL-only service does. Use the identity from a persisted result to retrieve or execute it. Do not reconstruct identities from arbitrary URLs.

```php
$result = $factory->getDeferredProcessor()->executeDeferred($identity);
foreach ($factory->getRecords()->listPending() as $record) {
    $factory->getDeferredProcessor()->executeDeferred($record->getIdentity(), false);
}
```

Blocking execution waits and returns an already completed result. Non-blocking execution returns `null` when a lock cannot be obtained, the target has gone away, or another worker has completed it. Completed records remain available. `getResult()` can return a pending result. Inspect record enumeration when pending/completed state matters.

For HTTP delivery, stream a `ReadableImageResultInterface` or redirect to a URL-only result. Reading or converting a deferred result with `asSource()` materializes it. Check `LocalImageResultInterface` before calling `getPath()`. A missing record produces `null`, a changed source produces `SourceVersionMismatchException`, and a missing source produces `SourceUnavailableException`. The example HTTP controller, CLI worker and consumer are in `examples/consumer`.

Warm completed results trust their records. They do not probe target content or reread output dimensions. If external software removes content, call `invalidate($identity)` and execute it again. `withBypassCache(true)` regenerates immediately under the same target identity and assigns a new persisted content revision.

## Pictures, exports and chaining

`ResponsivePictureGeneratorInterface` accepts the same picture configuration classes. It preserves source ordering, density descriptors, format mappings and deduplication. It deduplicates by result identity and expands URLs when `getImg()` or `getSources()` is called. Raw attributes retain result objects.

An unchanged source can retain its public URL with `withSkipIfDimensionsMatch(true)`. Without a public URL the bytes are copied into managed content. Without this option the encoder still runs, preserving existing encoding behavior.

`withDestination($absolutePath)` writes an immediate, atomically published export under a destination lock. Exports are caller-owned and pruning never removes them. The returned path and stream refer to the export. Its identity, URL and `asSource()` remain associated with the managed result. Wrap the export in a registered filesystem source if it must remain chainable independently of the cache. Do not place exports inside managed directories or use symlink aliases of managed paths. Direct overwrite of the source and exports inside the default factory cache are rejected.

Use `$parent->asSource()` to resize a readable result again. Parent materialization happens before child locks are acquired. Its version is the persisted content revision, so bypass regeneration changes child identities. Deleting a parent marks registered result-backed source lifecycles stale through retryable cleanup. Missing parents fail explicitly. Reserve the storage identifier `image-results` for this mechanism.

## Application adapters

Implement `ImageSourceInterface` for VFS, object storage or other streams. `getVersion()` must change when bytes change, and the resolver must support the expected snapshot or reject it. Add `PublicImageSourceInterface` when a current public URL can be generated. Use `FilesystemImageSource` and `FilesystemSourceResolver` for local files instead of making a URL look like a filesystem path.

Implement `ImageDecoderEncoderInterface` for custom local processing or decorate it for instrumentation. `ImagineImageDecoderEncoder` accepts custom raster and SVG Imagine instances. It is the explicit boundary to Imagine and translates failures into library exceptions with the original cause. Source inspection is independently replaceable through `FilesystemImageConfiguration::withInspector()`.

For a remote transformation service, implement `ImageBackendInterface`. `prepare()` receives calculated coordinates and effective options, then returns serializable `PreparedTarget` data. `execute()` produces a description, never a cached URL. The backend generates URLs dynamically and handles deletion idempotently. A URL-only example lives in `examples/consumer/UrlOnlyBackend.php`. Production adapters, signed URL expiry handling, backend fallback and Contao service-container integration are application responsibilities.
