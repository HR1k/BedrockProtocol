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
use pocketmine\network\mcpe\protocol\types\ServerJoinInformation;

final class ServerJoinInfoPreviewTest extends TestCase{

	private static function encode(ServerJoinInformation $joinInfo, int $protocolId) : string{
		$out = new ByteBufferWriter();
		$joinInfo->write($out, $protocolId);
		return $out->getData();
	}

	public function testDefaultJoinInfoHasNoManagedChatAndRetainsLegacyBytes() : void{
		$joinInfo = new ServerJoinInformation(null, null, null);
		self::assertSame("\x00\x00\x00", self::encode($joinInfo, ProtocolInfo::PROTOCOL_1_26_60));
		self::assertSame("\x00\x00\x00\x00", self::encode($joinInfo, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_20));
		self::assertSame("\x00\x00\x00\x00", self::encode($joinInfo, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26));

		$decoded = ServerJoinInformation::read(new ByteBufferReader(self::encode($joinInfo, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26)), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertNull($decoded->getManagedChatEnabled());
	}

	public function testManagedChatBooleanIsOptionalAndVersionGated() : void{
		foreach([false, true] as $value){
			$joinInfo = new ServerJoinInformation(null, null, null, $value);
			$legacy = self::encode($joinInfo, ProtocolInfo::PROTOCOL_1_26_60);
			$preview = self::encode($joinInfo, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
			self::assertSame("\x00\x00\x00", $legacy);
			self::assertSame("\x00\x00\x00\x01" . ($value ? "\x01" : "\x00"), $preview);
			$decoded = ServerJoinInformation::read(new ByteBufferReader($preview), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
			self::assertSame($value, $decoded->getManagedChatEnabled());
		}
	}

	public function testExistingProtocolAvailabilityIsUnchanged() : void{
		self::assertSame(ProtocolInfo::PROTOCOL_1_26_60, ProtocolInfo::CURRENT_PROTOCOL);
		self::assertContains(ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26, ProtocolInfo::ACCEPTED_PROTOCOL);
	}
}
