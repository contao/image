# Inspection and encoding support

Format detection uses bytes rather than filenames. Misnamed and extensionless sources work. Inspection support does not imply that the selected encoder can produce the same format.

| Format | Intrinsic inspection | Default encoding |
| --- | --- | --- |
| JPEG | Incremental marker and EXIF scan | GD or configured Imagine driver |
| PNG | Incremental chunks, including trailing EXIF | GD or configured Imagine driver |
| GIF | Logical screen header | GD or configured Imagine driver |
| WebP | RIFF chunks, VP8/VP8L/VP8X and EXIF | Depends on installed driver build |
| SVG | Incremental root XML, external resources disabled | Configurable contao/imagine-svg driver |
| SVGZ | Incremental gzip inflation and root XML | Configurable contao/imagine-svg driver |
| BMP and other native PHP formats | Buffered `getimagesize()` | Depends on installed Imagine driver |
| AVIF, HEIC, JXL and TIFF | Native PHP when available, otherwise explicit Imagine inspection fallback | Driver and codec-build dependent, commonly Imagick |

JPEG inspection continues through orientation-bearing markers. PNG and WebP container layouts can require reading more data, including data after the image payload. SVG parsing reads until the root attributes are available. SVG dimensions retain absolute, relative/viewBox and undefined semantics. The library makes no fixed header-byte limit promise.

Configure `StreamImageInspector(new ImagineImageInspector($driver))` through the filesystem configuration when native inspection does not recognize an installed driver's formats. This fallback is deliberately Imagine-specific. It buffers the stream and opens it with the configured driver. It does not change the encoding backend or silently fall back to another one.

An unsupported transformation produces a library decoding or encoding exception with the underlying driver error retained. The suite exercises GD, SVG and SVGZ transformations and includes an Imagick CI job. AVIF, HEIC, JXL and TIFF availability is not promised across every platform or binary build.
