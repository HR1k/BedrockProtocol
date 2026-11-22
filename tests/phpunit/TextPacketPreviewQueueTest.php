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
use function strlen;

final class TextPacketPreviewQueueTest extends TestCase{

	private static function encode(TextPacket $packet, int $protocolId) : string{
		$writer = new ByteBufferWriter();
		$packet->encode($writer, $protocolId);
		return $writer->getData();
	}

	public function testTranslatedMessageIncludesOnlyOneNewPreviewByte() : void{
		$packet = TextPacket::translation("orients.test", ["One", "Two"]);
		$legacy = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$preview = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(strlen($legacy) + 1, strlen($preview));

		$decoded = new TextPacket();
		$decoded->decode(new ByteBufferReader($preview), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertFalse($decoded->queue);
		self::assertSame($packet->parameters, $decoded->parameters);
		self::assertSame($packet->message, $decoded->message);
	}

	public function testJukeboxPopupSupportsQueueWithoutChangingOldProtocols() : void{
		$packet = TextPacket::jukeboxPopup("orients.song", ["Title"]);
		$packet->queue = true;

		$legacy = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$preview = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(strlen($legacy) + 1, strlen($preview));

		$decoded = new TextPacket();
		$decoded->decode(new ByteBufferReader($preview), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertTrue($decoded->queue);
		self::assertSame(["Title"], $decoded->parameters);
	}

	public function testRawMessageSerializationUnchanged() : void{
		$packet = TextPacket::raw("unchanged");
		$legacy = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$preview = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame($legacy, $preview);
	}

	public function testTranslatedPopupQueueUses2250AndLaterOnly() : void{
		$packet = TextPacket::translatedPopup("orients.popup", ["OK"]);
		$packet->queue = true;
		$legacy = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$firstChanged = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_23);
		self::assertSame(strlen($legacy) + 1, strlen($firstChanged));
	}
}
