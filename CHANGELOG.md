# Changelog

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


## [1.2.0] (2023-05-30)

 * Add EXIF, IPTC, XMP metadata handling for JPEG, PNG, WEBP, GIF, HEIC, JXL, AVIF. [#93], [#95], [#96]
 * Improve file name hashing algorithm. [#90]
 * Fix wrong exception class being thrown.
 * Deprecate `ResizeConfiguration::MODE_PROPORTIONAL`. [#97]
 * Add new methods `getRawImg()` and `getRawSources()` to Picture. [#98]

## [1.1.2] (2022-08-16)

 * Switch to Symfony's version of the Path helper. [#89]

## [1.1.1] (2021-07-06)

 * Fix resize calculation ending up with zero. [#82]

## [1.1.0] (2021-03-18)

 * Add namespaced exceptions and a specific `FileNotExistsException`. [#79]

## [1.0.3] (2020-11-20)

 * Support PHP version 8.0. [#74]
 * Revert fix for Gmagick bug `No encode delegate for this image format`. [#70]

## [1.0.2] (2020-06-13)

 * Handle JSON errors when decoding. [#68]
 * Fix Gmagick bug `No encode delegate for this image format`. [#70]
 * Compatibility with `contao/imagine-svg` 1.0. [#71]

## [1.0.1] (2019-11-25)

 * Handle JSON errors. [#63]
 * Compatibility with Symfony 5. [#62]
 * Canonicalize relative paths of deferred images. [#64]
 * Fix rounding errors of important part values. [#60]

## [1.0.0] (2019-08-08)

 * Add upgrade documentation (UPGRADE.md file).

## [1.0.0-beta4] (2019-07-29)

 * Remove unnecessary interfaces. [#57]
 * Don’t throw exceptions for malformed EXIF data. [#56]
 * Fix race conditions. [#55]

## [1.0.0-beta3] (2019-07-04)

 * Add support for multiple image formats in pictures. [#53]

## [1.0.0-beta2] (2019-07-02)

 * Add skipIfDimensionsMatch flag to ResizeOptions. [#52]
 * Autorotate images based on EXIF metadata. [#52]

## [1.0.0-beta1] (2019-06-16)

 * Add deferred image resizing. [#50]
 * Use important part with relative values as fractions. [#51]
 * Increase PHP requirement to 7.1.
 * Fix bug with imagine array options.
 * Remove unnecessary requirements for `ext-libxml`, `ext-xmlreader` and `contao/imagine-svg`.

## [0.3.9] (2019-01-28)

 * Fix bug with wrong file permissions. [#49]

## [0.3.8] (2019-01-27)

 * Use atomic file operations to save images.
 * Compatibility with Imagine 1.0.
 * Fix bug with `x` descriptor for small source images. [#48]
 * Don’t generate SVG images with different densities. [#46]

## [0.3.7] (2018-06-14)

 * Support for version 0.2 of `contao/imagine-svg`.
 * Add contents for the CHANGELOG.md file.

## [0.3.6] (2018-03-02)

 * Add compatibility with Symfony 4.0. [#42]
 * Fix bug with number formatting of density descriptor.

## [0.3.5] (2017-11-28)

 * Remove support for PHP 5.5.
 * Prefix certain global functions. [contao/core-bundle#1103]
 * Improve zlib stream check. [#41]
 * Fix bug with wrong densities. [#40]

## [0.3.4] (2017-05-20)

 * Add compatibility with imagine 0.7. [#35]
 * Convert images to RGB and strip metadata. [#37]

## [0.3.3] (2017-04-05)

 * Make resizer path relative to cache dir. [#32]

## [0.3.2] (2017-04-05)

 * Round resize configuration after scaling. [#31]
 * Fix bug with `LC_NUMERIC` locale. [#28]
 * Use imagine options in cache path hash. [#26]

## [0.3.1] (2016-11-22)

 * Add support for interlace option. [#24]
 * Better performance of `getDimensions()` for SVG images. [#23]
 * Fix bug with duplicate sources in `srcset` attribute. [#21]

## [0.3.0] (2016-08-28)

 * Remove constructors from interfaces. [#10]
 * Throw exceptions for unsupported resize modes.
 * Better test coverage.
 * Prefix parameter for `getUrl()`. [#12]
 * Base the `x` descriptor on the real image size. [#18]
 * Use webmozart/path-util to generate URLs. [#11], [#16]

## [0.2.0] (2016-08-04)

 * Improve constructor arguments order of `Image` and `Resizer`.
 * Declare `Resizer` methods as protected to be accessible by `contao/core-bundle`.
 * Rename `Resizer::resize` `path` argument to `cacheDir`.

## [0.1.0] (2016-07-29)

 * Initial release

[Unreleased]: https://github.com/contao/image/compare/1.2.0...1.x
[1.2.0]: https://github.com/contao/image/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/contao/image/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/contao/image/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/contao/image/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/contao/image/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/contao/image/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/contao/image/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/contao/image/compare/1.0.0-beta4...1.0.0
[1.0.0-beta4]: https://github.com/contao/image/compare/1.0.0-beta3...1.0.0-beta4
[1.0.0-beta3]: https://github.com/contao/image/compare/1.0.0-beta2...1.0.0-beta3
[1.0.0-beta2]: https://github.com/contao/image/compare/1.0.0-beta1...1.0.0-beta2
[1.0.0-beta1]: https://github.com/contao/image/compare/0.3.9...1.0.0-beta1
[0.3.9]: https://github.com/contao/image/compare/0.3.8...0.3.9
[0.3.8]: https://github.com/contao/image/compare/0.3.7...0.3.8
[0.3.7]: https://github.com/contao/image/compare/0.3.6...0.3.7
[0.3.6]: https://github.com/contao/image/compare/0.3.5...0.3.6
[0.3.5]: https://github.com/contao/image/compare/0.3.4...0.3.5
[0.3.4]: https://github.com/contao/image/compare/0.3.3...0.3.4
[0.3.3]: https://github.com/contao/image/compare/0.3.2...0.3.3
[0.3.2]: https://github.com/contao/image/compare/0.3.1...0.3.2
[0.3.1]: https://github.com/contao/image/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/contao/image/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/contao/image/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/contao/image/commits/0.1.0

[#98]: https://github.com/contao/image/issues/98
[#97]: https://github.com/contao/image/issues/97
[#96]: https://github.com/contao/image/issues/96
[#95]: https://github.com/contao/image/issues/95
[#93]: https://github.com/contao/image/issues/93
[#90]: https://github.com/contao/image/issues/90
[#89]: https://github.com/contao/image/issues/89
[#82]: https://github.com/contao/image/issues/82
[#79]: https://github.com/contao/image/issues/79
[#74]: https://github.com/contao/image/issues/74
[#71]: https://github.com/contao/image/issues/71
[#70]: https://github.com/contao/image/issues/70
[#68]: https://github.com/contao/image/issues/68
[#64]: https://github.com/contao/image/issues/64
[#63]: https://github.com/contao/image/issues/63
[#62]: https://github.com/contao/image/issues/62
[#60]: https://github.com/contao/image/issues/60
[#57]: https://github.com/contao/image/issues/57
[#56]: https://github.com/contao/image/issues/56
[#55]: https://github.com/contao/image/issues/55
[#53]: https://github.com/contao/image/issues/53
[#52]: https://github.com/contao/image/issues/52
[#51]: https://github.com/contao/image/issues/51
[#50]: https://github.com/contao/image/issues/50
[#49]: https://github.com/contao/image/issues/49
[#48]: https://github.com/contao/image/issues/48
[#46]: https://github.com/contao/image/issues/46
[#42]: https://github.com/contao/image/issues/42
[contao/core-bundle#1103]: https://github.com/contao/core-bundle/issues/1103
[#41]: https://github.com/contao/image/issues/41
[#40]: https://github.com/contao/image/issues/40
[#37]: https://github.com/contao/image/issues/37
[#35]: https://github.com/contao/image/issues/35
[#32]: https://github.com/contao/image/issues/32
[#31]: https://github.com/contao/image/issues/31
[#28]: https://github.com/contao/image/issues/28
[#26]: https://github.com/contao/image/issues/26
[#24]: https://github.com/contao/image/issues/24
[#23]: https://github.com/contao/image/issues/23
[#21]: https://github.com/contao/image/issues/21
[#18]: https://github.com/contao/image/issues/18
[#16]: https://github.com/contao/image/issues/16
[#12]: https://github.com/contao/image/issues/12
[#11]: https://github.com/contao/image/issues/11
[#10]: https://github.com/contao/image/issues/10
