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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\utils\Binary;

final class PreviewHashedItemStackTest extends TestCase{

	public function test2259SignedBlockHashRoundTripForItemStackDescriptor() : void{
		// The 26.70 network ID for stone has the top bit set and is a negative signed hash.
		$hash = -2144268767;
		$wrapper = new ItemStackWrapper(27, new ItemStack(5, 0, 1, $hash, ""));
		$out = new ByteBufferWriter();
		CommonTypes::putNetworkItemStackDescriptor($out, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26, $wrapper);

		$decoded = CommonTypes::getNetworkItemStackDescriptor(new ByteBufferReader($out->getData()), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame($hash, $decoded->getItemStack()->getBlockRuntimeId());
		self::assertSame(27, $decoded->getStackId());
		self::assertSame(5, $decoded->getItemStack()->getId());
	}

	public function test2259NormalizesClientUnsignedBlockHash() : void{
		$unsigned = Binary::unsignInt(-2144268767);
		$wrapper = new ItemStackWrapper(0, new ItemStack(5, 0, 1, $unsigned, ""));
		$out = new ByteBufferWriter();
		CommonTypes::putNetworkItemStackDescriptor($out, ProtocolInfo::PROTOCOL_1_26_60, $wrapper);

		$legacy = CommonTypes::getNetworkItemStackDescriptor(new ByteBufferReader($out->getData()), ProtocolInfo::PROTOCOL_1_26_60);
		$preview = CommonTypes::getNetworkItemStackDescriptor(new ByteBufferReader($out->getData()), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame($unsigned, $legacy->getItemStack()->getBlockRuntimeId());
		self::assertSame(-2144268767, $preview->getItemStack()->getBlockRuntimeId());
	}

	public function test2259PositiveIdsRoundTrip() : void{
		$wrapper = new ItemStackWrapper(1, new ItemStack(5, 0, 1, 42, ""));
		$out = new ByteBufferWriter();
		CommonTypes::putNetworkItemStackDescriptor($out, ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26, $wrapper);
		$decoded = CommonTypes::getNetworkItemStackDescriptor(new ByteBufferReader($out->getData()), ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26);
		self::assertSame(42, $decoded->getItemStack()->getBlockRuntimeId());
	}
}
