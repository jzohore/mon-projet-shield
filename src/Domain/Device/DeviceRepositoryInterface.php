<?php

namespace App\Domain\Device;

interface DeviceRepositoryInterface
{
    public function save(Device $device): void;

}
