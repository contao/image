# Changelog

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


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

[Unreleased]: https://github.com/contao/image/compare/0.3.9...HEAD
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
