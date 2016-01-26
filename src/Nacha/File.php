<?php

namespace Nacha;

use Nacha\Record\Block;
use Nacha\Record\FileHeader;
use Nacha\Record\FileFooter;

class File {

	const BREAK_CRLF = "\r\n";
	const BREAK_CR = "\r";
	const BREAK_LF = "\n";

	private $header;
	/** @var Batch[] */
	private $batches = [];

	private $lineBreak;

	public function __construct($lineBreak = self::BREAK_LF) {
		$this->lineBreak = $lineBreak;
		$this->header = new FileHeader();
	}

	public function getHeader() {
		return $this->header;
	}

	public function getBatches() {
		return $this->batches;
	}

	public function addBatch(Batch $batch) {
		$batch->setLineBreak($this->getLineBreak());

		$this->batches[] = $batch;
		$batch->getHeader()->setBatchNumber(count($this->batches));
	}

	private function getHash() {
		$hash = 0;
		foreach ($this->batches as $batch) {
			$hash += $batch->getEntryHash();
		}
		return substr((string)$hash, -10); // only take 10 digits from end of string to 10
	}

	public function setLineBreak($value) {
		$this->lineBreak = $value;
	}

	public function getLineBreak() {
		return $this->lineBreak;
	}

	public function __toString() {
		$batches = '';

		$fileFooter = (new FileFooter)
			->setEntryHash($this->getHash())
			->setBatchCount(count($this->batches));

		$totalDebits     = 0;
		$totalCredits    = 0;
		$totalEntryCount = 0;

		foreach ($this->batches as $batch) {
			$totalEntryCount += $batch->getTotalEntryCount();
			$totalDebits     += $batch->getTotalDebitAmount(); // is this total amount of debits, or entries?
			$totalCredits    += $batch->getTotalCreditAmount(); // is this total amount of credits, or entries?

			$batches .= $batch.$this->lineBreak;
		}

		// block padding
		// num entries + num batches header/footer + file header/footer
		$totalRecords = $totalEntryCount + (count($this->batches) * 2) + 2;
		$blocksNeeded = (ceil($totalRecords / 10) * 10) - $totalRecords;

		$block = '';
		for ($x=0; $x<$blocksNeeded % 10; $x++) {
			$block .= (new Block).$this->lineBreak;
		}

		$fileFooter->setBlockCount(ceil($totalRecords / 10));
		$fileFooter->setEntryAddendaCount($totalEntryCount);
		$fileFooter->setTotalDebits($totalDebits);
		$fileFooter->setTotalCredits($totalCredits);

		$output = $this->header.$this->lineBreak.$batches.$fileFooter.$this->lineBreak.$block;

		return rtrim($output, $this->lineBreak);
	}

}