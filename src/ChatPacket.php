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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\chat\ChatFormatting;
use pocketmine\network\mcpe\protocol\types\chat\SignedChat;

/**
 * Mojang Managed Chat (Preview 26.70); the server must not rewrite signed fields.
 */
final class ChatPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::CHAT_PACKET;

	private SignedChat $signedChat;
	private ?string $roomId;
	private ?ChatFormatting $formatting;

	/**
	 * @generate-create-func
	 */
	public static function create(SignedChat $signedChat, ?string $roomId, ?ChatFormatting $formatting) : self{
		$result = new self;
		$result->signedChat = $signedChat;
		$result->roomId = $roomId;
		$result->formatting = $formatting;
		return $result;
	}

	public function getSignedChat() : SignedChat{ return $this->signedChat; }

	public function getRoomId() : ?string{ return $this->roomId; }

	public function getFormatting() : ?ChatFormatting{ return $this->formatting; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			throw new PacketDecodeException("ChatPacket requires protocol 2259 or newer");
		}
		$this->signedChat = SignedChat::read($in);
		$this->roomId = CommonTypes::readOptional($in, CommonTypes::getString(...));
		$this->formatting = CommonTypes::readOptional($in, ChatFormatting::read(...));
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			throw new \InvalidArgumentException("ChatPacket requires protocol 2259 or newer");
		}
		$this->signedChat->write($out);
		CommonTypes::writeOptional($out, $this->roomId, CommonTypes::putString(...));
		CommonTypes::writeOptional($out, $this->formatting, static fn(ByteBufferWriter $out, ChatFormatting $formatting) => $formatting->write($out));
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleChat($this);
	}
}
