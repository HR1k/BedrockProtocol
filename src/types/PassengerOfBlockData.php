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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Spec name: PassengerOfBlockArguments
 */
final class PassengerOfBlockData{

	public function __construct(
		private readonly BlockPosition $blockPos,
		private readonly Vector3 $offset,
		private readonly float $rotation,
		private readonly float $rotationLimit,
		private readonly EmoteType $emoteType,
		private readonly int $blockType = 0,
		private readonly ?UuidInterface $packUuid = null
	){
	}

	public function getBlockPos() : BlockPosition{
		return $this->blockPos;
	}

	public function getOffset() : Vector3{
		return $this->offset;
	}

	public function getRotation() : float{
		return $this->rotation;
	}

	public function getRotationLimit() : float{
		return $this->rotationLimit;
	}

	public function getEmoteType() : EmoteType{
		return $this->emoteType;
	}

	/** The block hashed serialized ID added in Preview .23. */
	public function getBlockType() : int{ return $this->blockType; }

	/** The behavior pack UUID added in Preview .24, null when not supplied by an older caller. */
	public function getPackUuid() : ?UuidInterface{ return $this->packUuid; }

	public function write(ByteBufferWriter $out, int $protocolId = ProtocolInfo::PROTOCOL_1_26_60) : void{
		CommonTypes::putBlockPosition($out, $this->blockPos);
		CommonTypes::putVector3($out, $this->offset);
		LE::writeFloat($out, $this->rotation);
		LE::writeFloat($out, $this->rotationLimit);
		Byte::writeUnsigned($out, $this->emoteType->value);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_23){
			LE::writeUnsignedLong($out, $this->blockType);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			CommonTypes::putUUID($out, $this->packUuid ?? Uuid::fromString(Uuid::NIL));
		}
	}

	public static function read(ByteBufferReader $in, int $protocolId = ProtocolInfo::PROTOCOL_1_26_60) : self{
		$blockPos = CommonTypes::getBlockPosition($in);
		$offset = CommonTypes::getVector3($in);
		$rotation = LE::readFloat($in);
		$rotationLimit = LE::readFloat($in);
		$emoteType = EmoteType::fromPacket(Byte::readUnsigned($in));
		$blockType = $protocolId >= ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_23 ? LE::readUnsignedLong($in) : 0;
		$packUuid = $protocolId >= ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26 ? CommonTypes::getUUID($in) : null;

		return new self($blockPos, $offset, $rotation, $rotationLimit, $emoteType, $blockType, $packUuid);
	}
}
