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
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\EmoteType;
use pocketmine\network\mcpe\protocol\types\PassengerOfBlockData;
use Ramsey\Uuid\Uuid;
use function strlen;
use function substr;

final class PassengerAndSoundPreviewTest extends TestCase{

	private static function passenger() : PassengerOfBlockData{
		return new PassengerOfBlockData(
			new BlockPosition(2, 64, 3),
			new Vector3(0.25, 0.5, 0.75),
			1.5,
			2.5,
			EmoteType::STANDING,
			0x1122334455,
			Uuid::fromString("11223344-5566-7788-99aa-bbccddeeff00")
		);
	}

	private static function sound() : PlaySoundPacket{
		return PlaySoundPacket::create("random.click", 1.0, 2.0, 3.0, 0.5, 1.25, 0, false, null, null);
	}

	private static function encodePacket(DataPacket $packet, int $protocolId) : string{
		$out = new ByteBufferWriter();
		$packet->encode($out, $protocolId);
		return $out->getData();
	}

	public function testPassengerBlockDataAddsPreviewFieldsOnlyAfter2250() : void{
		$data = self::passenger();
		$legacy = new ByteBufferWriter();
		$data->write($legacy, ProtocolInfo::PROTOCOL_1_26_60);
		$preview23 = new ByteBufferWriter();
		$data->write($preview23, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_23);
		$preview26 = new ByteBufferWriter();
		$data->write($preview26, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);

		$oldBytes = $legacy->getData();
		$preview23Bytes = $preview23->getData();
		$preview26Bytes = $preview26->getData();
		self::assertSame($oldBytes, substr($preview23Bytes, 0, strlen($oldBytes)));
		self::assertSame($preview23Bytes, substr($preview26Bytes, 0, strlen($preview23Bytes)));
		self::assertSame(strlen($oldBytes) + 8, strlen($preview23Bytes));
		self::assertSame(strlen($oldBytes) + 24, strlen($preview26Bytes));

		$decoded = PassengerOfBlockData::read(new ByteBufferReader($preview26Bytes), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(0x1122334455, $decoded->getBlockType());
		self::assertSame("11223344-5566-7788-99aa-bbccddeeff00", $decoded->getPackUuid()?->toString());

		$old = PassengerOfBlockData::read(new ByteBufferReader($oldBytes), ProtocolInfo::PROTOCOL_1_26_60);
		self::assertSame(0, $old->getBlockType());
		self::assertNull($old->getPackUuid());
	}

	public function testSetPassengerPacketUsesVersionSpecificFields() : void{
		$packet = SetPassengerOfBlockPacket::create(99, self::passenger());
		$legacy = self::encodePacket($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$preview = self::encodePacket($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(strlen($legacy) + 24, strlen($preview));

		$decoded = new SetPassengerOfBlockPacket();
		$decoded->decode(new ByteBufferReader($preview), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(0x1122334455, $decoded->getPassengerData()?->getBlockType());
	}

	public function testPlaySoundLegacyBytesRemainUnchanged() : void{
		$packet = self::sound();
		$packet->setStartDelaySeconds(1.5);
		$packet->setAttachedEntityRuntimeId(12345);
		$legacyBytes = self::encodePacket($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$preview23Bytes = self::encodePacket($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_23);
		$preview26Bytes = self::encodePacket($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame($legacyBytes, substr($preview23Bytes, 0, strlen($legacyBytes)));
		self::assertSame($preview23Bytes, substr($preview26Bytes, 0, strlen($preview23Bytes)));

		$decoded = new PlaySoundPacket();
		$decoded->decode(new ByteBufferReader($preview26Bytes), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertEqualsWithDelta(1.5, $decoded->getStartDelaySeconds(), 0.00001);
		self::assertSame(12345, $decoded->getAttachedEntityRuntimeId());
	}

	public function testPlaySoundOptionalPreviewFieldsCanBeAbsent() : void{
		$packet = self::sound();
		$bytes = self::encodePacket($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		$decoded = new PlaySoundPacket();
		$decoded->decode(new ByteBufferReader($bytes), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertNull($decoded->getStartDelaySeconds());
		self::assertNull($decoded->getAttachedEntityRuntimeId());
	}

	public function testPreview2259Accepted() : void{
		self::assertSame(2225, ProtocolInfo::CURRENT_PROTOCOL);
		self::assertContains(2259, ProtocolInfo::ACCEPTED_PROTOCOL);
	}
}
