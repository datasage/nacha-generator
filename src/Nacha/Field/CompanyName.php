<?php

namespace Nacha\Field;

class CompanyName extends StringField {

	public function __construct($value) {
		$value = strtolower($value) == 'check destroyed' ? strtoupper($value) : $value;

		parent::__construct($value, 16);
	}
}
