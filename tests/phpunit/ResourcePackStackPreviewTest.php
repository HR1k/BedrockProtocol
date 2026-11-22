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
use pocketmine\network\mcpe\protocol\types\Experiments;
use function chr;
use function substr;

final class ResourcePackStackPreviewTest extends TestCase{

	private static function createPacket(bool $legacyEditorPacks) : ResourcePackStackPacket{
		return ResourcePackStackPacket::create(
			[],
			[],
			false,
			"1.26.60",
			new Experiments([], false),
			$legacyEditorPacks
		);
	}

	private static function encode(ResourcePackStackPacket $packet, int $protocolId) : string{
		$out = new ByteBufferWriter();
		$packet->encode($out, $protocolId);
		return $out->getData();
	}

	public function testLegacyEditorPackBooleanIsUnchanged() : void{
		foreach([false, true] as $enabled){
			$bytes = self::encode(self::createPacket($enabled), ProtocolInfo::PROTOCOL_1_26_60);
			self::assertSame($enabled ? "\x01" : "\x00", substr($bytes, -1));

			$decoded = new ResourcePackStackPacket();
			$decoded->decode(new ByteBufferReader($bytes), ProtocolInfo::PROTOCOL_1_26_60);
			self::assertSame($enabled, $decoded->useVanillaEditorPacks);
			self::assertNull($decoded->editorPackTypes);
		}
	}

	public function testPreviewEditorPackFlagsArePreserved() : void{
		foreach([0, 1, 2, 3] as $flags){
			$packet = self::createPacket(false);
			$packet->editorPackTypes = $flags;
			$bytes = self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
			self::assertSame(chr($flags), substr($bytes, -1));

			$decoded = new ResourcePackStackPacket();
			$decoded->decode(new ByteBufferReader($bytes), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
			self::assertSame($flags, $decoded->editorPackTypes);
			self::assertSame(($flags & 1) !== 0, $decoded->useVanillaEditorPacks);
		}
	}

	public function testPreviewUsesLegacyBooleanWhenNoFlagsProvided() : void{
		$bytes = self::encode(self::createPacket(true), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame("\x01", substr($bytes, -1));
	}

	public function testPreviewRejectsUnknownEditorPackBits() : void{
		$packet = self::createPacket(false);
		$packet->editorPackTypes = 4;
		$this->expectException(\InvalidArgumentException::class);
		self::encode($packet, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
	}

	public function testPreviewAcceptedForDirectServerTesting() : void{
		self::assertSame(ProtocolInfo::PROTOCOL_1_26_60, ProtocolInfo::CURRENT_PROTOCOL);
		self::assertContains(ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26, ProtocolInfo::ACCEPTED_PROTOCOL);
	}
}
