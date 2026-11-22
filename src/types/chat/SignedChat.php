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

namespace pocketmine\network\mcpe\protocol\types\chat;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Signed chat data must be relayed without changing its signed fields.
 */
final class SignedChat{
	public function __construct(
		private string $keyId,
		private string $senderId,
		private string $messageId,
		private string $scanText,
		private string $redactedText,
		private string $authorName,
		private string $signature
	){}

	public function getKeyId() : string{ return $this->keyId; }

	public function getSenderId() : string{ return $this->senderId; }

	public function getMessageId() : string{ return $this->messageId; }

	public function getScanText() : string{ return $this->scanText; }

	public function getRedactedText() : string{ return $this->redactedText; }

	public function getAuthorName() : string{ return $this->authorName; }

	public function getSignature() : string{ return $this->signature; }

	public static function read(ByteBufferReader $in) : self{
		return new self(
			CommonTypes::getString($in),
			CommonTypes::getString($in),
			CommonTypes::getString($in),
			CommonTypes::getString($in),
			CommonTypes::getString($in),
			CommonTypes::getString($in),
			CommonTypes::getString($in)
		);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->keyId);
		CommonTypes::putString($out, $this->senderId);
		CommonTypes::putString($out, $this->messageId);
		CommonTypes::putString($out, $this->scanText);
		CommonTypes::putString($out, $this->redactedText);
		CommonTypes::putString($out, $this->authorName);
		CommonTypes::putString($out, $this->signature);
	}
}
