<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Image;

final class TargetRequest
{
    public function __construct(private TargetIdentity $identity, private SourceVersion $source, private ImageTransformation $transformation)
    {
    }

    public function getIdentity(): TargetIdentity
    {
        return $this->identity;
    }

    public function getSource(): SourceVersion
    {
        return $this->source;
    }

    public function getTransformation(): ImageTransformation
    {
        return $this->transformation;
    }
}
