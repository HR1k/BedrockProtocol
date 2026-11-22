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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\VirtualContainerCloseCause;
use pocketmine\network\mcpe\protocol\types\inventory\VirtualContainerCloseKind;

/**
 * Coordinates closing a virtual container session (protocol 2259+).
 */
final class VirtualContainerClosePacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::VIRTUAL_CONTAINER_CLOSE_PACKET;

	private int $sessionId;
	private VirtualContainerCloseKind $kind;
	private VirtualContainerCloseCause $cause;

	/**
	 * @generate-create-func
	 */
	public static function create(int $sessionId, VirtualContainerCloseKind $kind, VirtualContainerCloseCause $cause) : self{
		$result = new self;
		$result->sessionId = $sessionId;
		$result->kind = $kind;
		$result->cause = $cause;
		return $result;
	}

	public function getSessionId() : int{ return $this->sessionId; }

	public function getKind() : VirtualContainerCloseKind{ return $this->kind; }

	public function getCause() : VirtualContainerCloseCause{ return $this->cause; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			throw new PacketDecodeException("Virtual container packets require protocol 2259 or newer");
		}
		$this->sessionId = VarInt::readUnsignedLong($in);
		if($this->sessionId === 0){
			throw new PacketDecodeException("Virtual container session ID cannot be zero");
		}
		$this->kind = VirtualContainerCloseKind::fromPacket(Byte::readUnsigned($in));
		$this->cause = VirtualContainerCloseCause::fromPacket(Byte::readUnsigned($in));
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			throw new \InvalidArgumentException("Virtual container packets require protocol 2259 or newer");
		}
		if($this->sessionId === 0){
			throw new \InvalidArgumentException("Virtual container session ID cannot be zero");
		}
		VarInt::writeUnsignedLong($out, $this->sessionId);
		Byte::writeUnsigned($out, $this->kind->value);
		Byte::writeUnsigned($out, $this->cause->value);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleVirtualContainerClose($this);
	}
}
