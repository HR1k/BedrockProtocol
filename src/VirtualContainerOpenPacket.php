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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use function count;
use function strlen;

/**
 * Opens an in-memory container not backed by an entity or block (protocol 2259+).
 */
final class VirtualContainerOpenPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::VIRTUAL_CONTAINER_OPEN_PACKET;

	private int $containerId;
	private int $sessionId;
	private string $title;
	/** @var ItemStackWrapper[] */
	private array $slots;

	/**
	 * @generate-create-func
	 * @param ItemStackWrapper[] $slots
	 */
	public static function create(int $containerId, int $sessionId, string $title, array $slots) : self{
		$result = new self;
		$result->containerId = $containerId;
		$result->sessionId = $sessionId;
		$result->title = $title;
		$result->slots = $slots;
		return $result;
	}

	public function getContainerId() : int{ return $this->containerId; }

	public function getSessionId() : int{ return $this->sessionId; }

	public function getTitle() : string{ return $this->title; }

	/** @return ItemStackWrapper[] */
	public function getSlots() : array{ return $this->slots; }

	private static function checkProtocol(int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			throw new PacketDecodeException("Virtual container packets require protocol 2259 or newer");
		}
	}

	private static function checkPayload(int $containerId, int $sessionId, string $title, int $slotCount) : void{
		if($containerId < 0 || $containerId > 255){
			throw new PacketDecodeException("Invalid virtual container window ID");
		}
		if($sessionId === 0){
			throw new PacketDecodeException("Virtual container session ID cannot be zero");
		}
		if(strlen($title) > 256){
			throw new PacketDecodeException("Virtual container title exceeds 256 bytes");
		}
		if($slotCount < 1 || $slotCount > 54){
			throw new PacketDecodeException("Virtual container must have 1 to 54 slots");
		}
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		self::checkProtocol($protocolId);
		$this->containerId = Byte::readUnsigned($in);
		$this->sessionId = VarInt::readUnsignedLong($in);
		$this->title = CommonTypes::getString($in);
		$slotCount = VarInt::readUnsignedInt($in);
		self::checkPayload($this->containerId, $this->sessionId, $this->title, $slotCount);
		$this->slots = [];
		for($i = 0; $i < $slotCount; ++$i){
			$this->slots[] = CommonTypes::getNetworkItemStackDescriptor($in, $protocolId);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		self::checkProtocol($protocolId);
		self::checkPayload($this->containerId, $this->sessionId, $this->title, count($this->slots));
		Byte::writeUnsigned($out, $this->containerId);
		VarInt::writeUnsignedLong($out, $this->sessionId);
		CommonTypes::putString($out, $this->title);
		VarInt::writeUnsignedInt($out, count($this->slots));
		foreach($this->slots as $slot){
			CommonTypes::putNetworkItemStackDescriptor($out, $protocolId, $slot);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleVirtualContainerOpen($this);
	}
}
