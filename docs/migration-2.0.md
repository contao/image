# Moving from 1.x to 2.0

Migrate consumers to the new API while running 1.3, then upgrade the library and PHP runtime. The shared engine requires PHP 8.3 in 1.3. Version 2.0 requires PHP 8.4. Stop workers and image generation, clear the complete image cache including legacy deferred records and the new namespace, and restart with the migrated application. Old cache identities and deferred records are not a 2.0 migration format.

| Legacy API | Shared 1.3 and 2.0 API |
| --- | --- |
| `Image` and `ImageInterface` | `ImageSourceInterface`, `FilesystemImageSource` and a resolver |
| `ResizerInterface` | `ImageResizerInterface` |
| `DeferredResizerInterface` | `DeferredImageProcessorInterface` |
| `DeferredImageStorageInterface` | `TargetRecordStorageInterface` plus content and information storage |
| `PictureGeneratorInterface` and `PictureInterface` | `ResponsivePictureGeneratorInterface` and `ResponsivePictureInterface` |
| `ResizeOptions` | `ProcessingOptions` and `EncodingOptions` |
| Imagine boxes and points at library boundaries | `Size`, `Point`, `Dimensions` and `TransformationCoordinates` |
| `getPath()` on every image | Check `LocalImageResultInterface`, otherwise use URL or stream capabilities |
| Mutable configuration setters | Clone-returning `with…()` methods |

`ResizeConfiguration`, `PictureConfiguration` and `PictureConfigurationItem` keep their names. Setters remain functional in 1.3 and are removed in 2.0. Use returned clones, for example `$configuration = $configuration->withWidth(640)`. The legacy `ResizeConfigurationInterface` is removed. Proportional mode remains supported, superseding its previous planned removal.

In 1.3, `LegacyOptionsConverter::convert($legacyOptions)` explicitly converts options for the new engine. Existing Imagine-returning methods keep their signatures and behavior. `FilesystemImageFactory` accepts an Imagine instance or codec in 1.3. Pass an explicit `ImagineImageDecoderEncoder` now because 2.0 requires a codec.

Contao factory integrations should resolve an `ImageSourceInterface` from the storage identifier and key, inject the shared resizer/picture generator, and return results with dynamic URLs. Configure the content URL prefix separately from source public URLs. This repository does not modify `contao/core-bundle` or register Contao container services.

Replace `DeferredResizer` subclasses with an `ImageResizerInterface` decorator for request policy, a codec decorator for local processing, or an `ImageBackendInterface` for storage/service behavior. Delegation to `StoredImageBackend` is explicit. Legacy protected hooks and Imagine exceptions remain intact on 1.3. New code catches library `ExceptionInterface`, inspects specific subclasses and uses `getPrevious()` for diagnostics.

A deferred HTTP request executes the identity and streams a readable result or redirects to a backend URL. A worker enumerates pending records and uses non-blocking execution. Completed records are retained, so old worker loops must not treat every stored record as pending. Examples include a runnable consumer, HTTP handler and CLI worker.

Sources based on VFS or external storage implement the stream contract directly, including immutable version semantics and resolver behavior. Public URLs are a separate optional capability. Readable results can become sources, but URL-only results cannot. Exports are caller-owned and should be registered as independent filesystem sources if they need independent lifecycle management.

## Run the representative consumer

From the repository root, use the PHP version matching the branch:

```sh
php examples/consumer/install.php 1.3.99
php examples/consumer/run.php
php examples/consumer/worker.php
```

On 2.x use `2.0.99` as the local override. Both runs use the same consumer requiring `^1.3 || ^2.0`. The installer writes an ignored Composer configuration with a local path repository, a package-version override and the executing PHP version. It does not publish a package or create a tag.
