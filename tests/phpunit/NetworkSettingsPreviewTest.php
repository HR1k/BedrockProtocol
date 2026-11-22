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
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\protocol\types\SctpRecoveryProfile;
use function chr;
use function strlen;
use function substr;

final class NetworkSettingsPreviewTest extends TestCase{

	private static function packet() : NetworkSettingsPacket{
		return NetworkSettingsPacket::create(1, CompressionAlgorithm::ZLIB, false, 0, 0.0);
	}

	private static function encode(NetworkSettingsPacket $packet, int $protocolId) : string{
		$out = new ByteBufferWriter();
		$packet->encode($out, $protocolId);
		return $out->getData();
	}

	public function testLegacyNetworkSettingsHaveNoRecoveryByte() : void{
		$packet = self::packet();
		$legacyBytes = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_60);
		$previewBytes = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);

		self::assertSame(strlen($legacyBytes) + 1, strlen($previewBytes));
		self::assertSame($legacyBytes, substr($previewBytes, 0, -1));
		self::assertSame("\x00", substr($previewBytes, -1));
	}

	public function testPreviewSctpRecoveryProfiles() : void{
		foreach(SctpRecoveryProfile::cases() as $profile){
			$packet = self::packet();
			$packet->setSctpRecoveryProfile($profile);
			$bytes = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
			self::assertSame(chr($profile->value), substr($bytes, -1));

			$decoded = new NetworkSettingsPacket();
			$decoded->decode(new ByteBufferReader($bytes), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
			self::assertSame($profile, $decoded->getSctpRecoveryProfile());
		}
	}
}
