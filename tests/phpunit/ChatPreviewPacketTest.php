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
use pocketmine\network\mcpe\protocol\types\chat\ChatFormatting;
use pocketmine\network\mcpe\protocol\types\chat\SignedChat;

final class ChatPreviewPacketTest extends TestCase{

	public function testChatPacketRoundTrip() : void{
		$signed = new SignedChat("key", "sender", "message", "hello", "", "Player", "signature");
		$formatting = new ChatFormatting("[VIP] ", null, null, "!");
		$packet = ChatPacket::create($signed, "room", $formatting);
		$writer = new ByteBufferWriter();
		$packet->encode($writer, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);

		$decoded = new ChatPacket();
		$decoded->decode(new ByteBufferReader($writer->getData()), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame("room", $decoded->getRoomId());
		self::assertSame("Player", $decoded->getSignedChat()->getAuthorName());
		self::assertSame("signature", $decoded->getSignedChat()->getSignature());
		self::assertSame("[VIP] ", $decoded->getFormatting()?->getNamePrefix());
		self::assertNull($decoded->getFormatting()?->getNamePostfix());
	}

	public function testChatPacketWithNoOptionalFields() : void{
		$packet = ChatPacket::create(new SignedChat("", "", "", "hi", "", "", ""), null, null);
		$writer = new ByteBufferWriter();
		$packet->encode($writer, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		$decoded = new ChatPacket();
		$decoded->decode(new ByteBufferReader($writer->getData()), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertNull($decoded->getRoomId());
		self::assertNull($decoded->getFormatting());
	}

	public function testLegacyDoesNotAdvertiseChatPacket() : void{
		self::assertSame(361, ProtocolInfo::CHAT_PACKET);
		self::assertInstanceOf(ChatPacket::class, PacketPool::getInstance()->getPacketById(361));
		$this->expectException(\InvalidArgumentException::class);
		$writer = new ByteBufferWriter();
		ChatPacket::create(new SignedChat("", "", "", "", "", "", ""), null, null)->encode($writer, ProtocolInfo::PROTOCOL_1_26_60);
	}
}
