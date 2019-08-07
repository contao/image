# Upgrade documentation

## Version 0.3.* to 1.0

### Add type hints

Scalar type hints for parameters and return type hints were added to all classes
and interfaces. Update derived classes accordingly.

### Remove interfaces

The following interfaces got removed, use the corresponding classes instead.

- `ImageDimensionsInterface`
- `ImportantPartInterface`
- `PictureConfigurationInterface`
- `PictureConfigurationItemInterface`
- `ResizeCalculatorInterface`
- `ResizeConfigurationInterface`
- `ResizeCoordinatesInterface`
- `ResizeOptionsInterface`

### New important part class

The `ImportantPart` class got reworked and now uses relative values as fractions
between `0` and `1` to represent X/Y coordinates and width/height dimensions.
Replace `getPosition()` with `getX()` and `getY()`. Replace `getSize()` with
`getWidth()` and `getHeight()`.
