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
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\protocol\types\SctpRecoveryProfile;

/**
 * This is the first packet sent by the server in a game session, in response to a network settings request (only if
 * protocol versions are a match). It includes values for things like which compression algorithm to use, size threshold
 * for compressing packets, and more.
 */
class NetworkSettingsPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::NETWORK_SETTINGS_PACKET;

	public const COMPRESS_NOTHING = 0;
	public const COMPRESS_EVERYTHING = 1;

	private int $compressionThreshold;
	private CompressionAlgorithm $compressionAlgorithm;
	private bool $enableClientThrottling;
	private int $clientThrottleThreshold;
	private float $clientThrottleScalar;
	private SctpRecoveryProfile $sctpRecoveryProfile = SctpRecoveryProfile::DEFAULT;

	/**
	 * Preserves the original five-argument create() API.
	 */
	public static function create(int $compressionThreshold, CompressionAlgorithm $compressionAlgorithm, bool $enableClientThrottling, int $clientThrottleThreshold, float $clientThrottleScalar) : self{
		$result = new self;
		$result->compressionThreshold = $compressionThreshold;
		$result->compressionAlgorithm = $compressionAlgorithm;
		$result->enableClientThrottling = $enableClientThrottling;
		$result->clientThrottleThreshold = $clientThrottleThreshold;
		$result->clientThrottleScalar = $clientThrottleScalar;
		return $result;
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function getCompressionThreshold() : int{
		return $this->compressionThreshold;
	}

	public function getCompressionAlgorithm() : CompressionAlgorithm{ return $this->compressionAlgorithm; }

	public function isEnableClientThrottling() : bool{ return $this->enableClientThrottling; }

	public function getClientThrottleThreshold() : int{ return $this->clientThrottleThreshold; }

	public function getClientThrottleScalar() : float{ return $this->clientThrottleScalar; }

	public function getSctpRecoveryProfile() : SctpRecoveryProfile{ return $this->sctpRecoveryProfile; }

	public function setSctpRecoveryProfile(SctpRecoveryProfile $profile) : void{ $this->sctpRecoveryProfile = $profile; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->compressionThreshold = LE::readUnsignedShort($in);
		$this->compressionAlgorithm = CompressionAlgorithm::fromPacket(LE::readUnsignedShort($in));
		$this->enableClientThrottling = CommonTypes::getBool($in);
		$this->clientThrottleThreshold = Byte::readUnsigned($in);
		$this->clientThrottleScalar = LE::readFloat($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			$this->sctpRecoveryProfile = SctpRecoveryProfile::fromPacket(Byte::readUnsigned($in));
		}else{
			$this->sctpRecoveryProfile = SctpRecoveryProfile::DEFAULT;
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeUnsignedShort($out, $this->compressionThreshold);
		LE::writeUnsignedShort($out, $this->compressionAlgorithm->value);
		CommonTypes::putBool($out, $this->enableClientThrottling);
		Byte::writeUnsigned($out, $this->clientThrottleThreshold);
		LE::writeFloat($out, $this->clientThrottleScalar);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_70_PREVIEW_26){
			Byte::writeUnsigned($out, $this->sctpRecoveryProfile->value);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleNetworkSettings($this);
	}
}
