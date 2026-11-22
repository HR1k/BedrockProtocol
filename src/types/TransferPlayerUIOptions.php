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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Optional transfer loading screen options introduced in Bedrock Preview 1.26.70.23.
 */
final class TransferPlayerUIOptions{
	public function __construct(
		private ?string $titleText = null,
		private ?string $bodyText = null,
		private ?bool $titleVisible = null,
		private ?bool $bodyVisible = null,
		private ?string $cancelButtonText = null,
		private ?bool $cancelButtonVisible = null,
		private ?float $cancelButtonDelaySeconds = null
	){}

	public function getTitleText() : ?string{ return $this->titleText; }

	public function getBodyText() : ?string{ return $this->bodyText; }

	public function getTitleVisible() : ?bool{ return $this->titleVisible; }

	public function getBodyVisible() : ?bool{ return $this->bodyVisible; }

	public function getCancelButtonText() : ?string{ return $this->cancelButtonText; }

	public function getCancelButtonVisible() : ?bool{ return $this->cancelButtonVisible; }

	public function getCancelButtonDelaySeconds() : ?float{ return $this->cancelButtonDelaySeconds; }

	public static function read(ByteBufferReader $in) : self{
		return new self(
			CommonTypes::readOptional($in, CommonTypes::getString(...)),
			CommonTypes::readOptional($in, CommonTypes::getString(...)),
			CommonTypes::readOptional($in, CommonTypes::getBool(...)),
			CommonTypes::readOptional($in, CommonTypes::getBool(...)),
			CommonTypes::readOptional($in, CommonTypes::getString(...)),
			CommonTypes::readOptional($in, CommonTypes::getBool(...)),
			CommonTypes::readOptional($in, LE::readFloat(...))
		);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::writeOptional($out, $this->titleText, CommonTypes::putString(...));
		CommonTypes::writeOptional($out, $this->bodyText, CommonTypes::putString(...));
		CommonTypes::writeOptional($out, $this->titleVisible, CommonTypes::putBool(...));
		CommonTypes::writeOptional($out, $this->bodyVisible, CommonTypes::putBool(...));
		CommonTypes::writeOptional($out, $this->cancelButtonText, CommonTypes::putString(...));
		CommonTypes::writeOptional($out, $this->cancelButtonVisible, CommonTypes::putBool(...));
		CommonTypes::writeOptional($out, $this->cancelButtonDelaySeconds, LE::writeFloat(...));
	}
}
