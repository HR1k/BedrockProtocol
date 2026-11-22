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
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\VirtualContainerCloseCause;
use pocketmine\network\mcpe\protocol\types\inventory\VirtualContainerCloseKind;
use function count;
use function str_repeat;

final class VirtualContainerPacketTest extends TestCase{

	private static function encode(Packet $packet, int $protocol) : string{
		$writer = new ByteBufferWriter();
		$packet->encode($writer, $protocol);
		return $writer->getData();
	}

	public function testPacketPoolRegistersPreviewPackets() : void{
		self::assertInstanceOf(VirtualContainerOpenPacket::class, PacketPool::getInstance()->getPacketById(366));
		self::assertInstanceOf(VirtualContainerClosePacket::class, PacketPool::getInstance()->getPacketById(367));
	}

	public function testOpenRoundTripWithNetIds() : void{
		$slot = new ItemStackWrapper(17, ItemStack::null());
		$packet = VirtualContainerOpenPacket::create(5, 300, "Test chest", [$slot, $slot]);
		$decoded = new VirtualContainerOpenPacket();
		$decoded->decode(new ByteBufferReader(self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26)), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(5, $decoded->getContainerId());
		self::assertSame(300, $decoded->getSessionId());
		self::assertSame("Test chest", $decoded->getTitle());
		self::assertCount(2, $decoded->getSlots());
	}

	public function testCloseRoundTrip() : void{
		$packet = VirtualContainerClosePacket::create(123456, VirtualContainerCloseKind::CLOSED, VirtualContainerCloseCause::USER_CLOSED);
		$decoded = new VirtualContainerClosePacket();
		$decoded->decode(new ByteBufferReader(self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26)), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(123456, $decoded->getSessionId());
		self::assertSame(VirtualContainerCloseKind::CLOSED, $decoded->getKind());
		self::assertSame(VirtualContainerCloseCause::USER_CLOSED, $decoded->getCause());
	}

	public function testLegacyProtocolsRejectVirtualPackets() : void{
		$this->expectException(PacketDecodeException::class);
		self::encode(VirtualContainerOpenPacket::create(1, 1, "Container", [new ItemStackWrapper(0, ItemStack::null())]), ProtocolInfo::PROTOCOL_1_26_60);
	}

	public function testVirtualContainerTitleLengthLimit() : void{
		$this->expectException(PacketDecodeException::class);
		self::encode(VirtualContainerOpenPacket::create(1, 1, str_repeat("x", 257), [new ItemStackWrapper(0, ItemStack::null())]), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
	}

	public function testPreviewProtocolAcceptedForTesting() : void{
		self::assertSame(2225, ProtocolInfo::CURRENT_PROTOCOL);
		self::assertContains(2259, ProtocolInfo::ACCEPTED_PROTOCOL);
	}
}
