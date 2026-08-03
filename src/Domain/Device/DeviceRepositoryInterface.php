<?php

declare(strict_types=1);

namespace App\Domain\Device;

interface DeviceRepositoryInterface
{
    public function save(Device $device): void;
}
