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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\TransferPlayerUIOptions;
use function strlen;
use function substr;

final class TransferPreviewTest extends TestCase{

	private static function encode(TransferPacket $packet, int $protocolId) : string{
		$out = new ByteBufferWriter();
		$packet->encode($out, $protocolId);
		return $out->getData();
	}

	public function testLegacyCreateApiAndMissingOptions() : void{
		$packet = TransferPacket::create("example.test", 19132, false, null);
		$legacy = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$preview = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(strlen($legacy) + 1, strlen($preview));
		self::assertSame($legacy, substr($preview, 0, -1));
		self::assertSame("\x00", substr($preview, -1));
	}

	public function testOptionalTransferScreenPropertiesRoundTrip() : void{
		$packet = TransferPacket::create("example.test", 19132, false, null);
		$packet->setTransferUIOptions(new TransferPlayerUIOptions("Joining", "Hello", true, false, "Cancel", true, 1.5));
		$encoded = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		$decoded = new TransferPacket();
		$decoded->decode(new ByteBufferReader($encoded), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame("Joining", $decoded->getTransferUIOptions()?->getTitleText());
		self::assertSame("Hello", $decoded->getTransferUIOptions()?->getBodyText());
		self::assertTrue($decoded->getTransferUIOptions()?->getTitleVisible());
		self::assertFalse($decoded->getTransferUIOptions()?->getBodyVisible());
		self::assertSame("Cancel", $decoded->getTransferUIOptions()?->getCancelButtonText());
		self::assertEqualsWithDelta(1.5, $decoded->getTransferUIOptions()?->getCancelButtonDelaySeconds(), 0.00001);
	}
}
