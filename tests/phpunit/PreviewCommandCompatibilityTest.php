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
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\sound\AudioContentPlaybackType;
use function strlen;
use function substr;

final class PreviewCommandCompatibilityTest extends TestCase{

	private static function commandBlock() : CommandBlockUpdatePacket{
		$packet = new CommandBlockUpdatePacket();
		$packet->isBlock = false;
		$packet->minecartActorRuntimeId = 10;
		$packet->command = "say hello";
		$packet->lastOutput = "";
		$packet->name = "Cmd";
		$packet->filteredName = "";
		$packet->shouldTrackOutput = false;
		$packet->tickDelay = 0;
		$packet->executeOnFirstTick = false;
		return $packet;
	}

	private static function encode(CommandBlockUpdatePacket $packet, int $protocol) : string{
		$out = new ByteBufferWriter();
		$packet->encode($out, $protocol);
		return $out->getData();
	}

	public function testCommandBlockPreviewRawFlagDoesNotTouch2225() : void{
		$p = self::commandBlock();
		$legacy = self::encode($p, ProtocolInfo::PROTOCOL_1_26_60);
		$previewDefault = self::encode($p, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(strlen($legacy) + 1, strlen($previewDefault));
		self::assertSame($legacy, substr($previewDefault, 0, -1));

		$p->lastOutputIsRawMessage = true;
		$preview = self::encode($p, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame("\x01", substr($preview, -1));

		$decoded = new CommandBlockUpdatePacket();
		$decoded->decode(new ByteBufferReader($preview), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertTrue($decoded->lastOutputIsRawMessage);
		self::assertSame("say hello", $decoded->command);
	}

	public function testCombinedGamepadAndMouseInput() : void{
		self::assertSame(5, InputMode::GAME_PAD_AND_MOUSE->value);
		self::assertSame(65, PlayerAuthInputFlags::SWAP_OFFHAND);
		self::assertSame(65, PlayerAuthInputFlags::INTERNAL_UPDATE);
		self::assertSame(2, AudioContentPlaybackType::CONCURRENT_MUSIC->value);
	}

	public function testCommandOutputMessageHasSafeDefault() : void{
		$message = new \pocketmine\network\mcpe\protocol\types\command\CommandOutputMessage();
		self::assertNull($message->rawMessage);
	}

	public function testDefaultProtocolRemainsLegacyWithPreviewAvailable() : void{
		self::assertSame(2225, ProtocolInfo::CURRENT_PROTOCOL);
		self::assertContains(2259, ProtocolInfo::ACCEPTED_PROTOCOL);
	}
}
