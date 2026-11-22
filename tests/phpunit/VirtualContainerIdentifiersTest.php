<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerUIIds;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

final class VirtualContainerIdentifiersTest extends TestCase{

	public function testPreviewContainerIdentifiers() : void{
		self::assertSame(39, WindowTypes::VIRTUAL_CONTAINER);
		self::assertSame(71, ContainerUIIds::VIRTUAL_CONTAINER);
	}

	public function testLegacyContainerIdentifiersRemainUnchanged() : void{
		self::assertSame(38, WindowTypes::RESERVED_CONTAINER_TYPE);
		self::assertSame(70, ContainerUIIds::RESERVED_CONTAINER_D);
	}
}
