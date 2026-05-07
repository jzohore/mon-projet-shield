<?php

namespace App\Domain\Common\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Encrypted {}
