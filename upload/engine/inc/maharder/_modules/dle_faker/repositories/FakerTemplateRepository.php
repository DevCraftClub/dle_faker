<?php

class FakerTemplateRepository extends BasisRepository {

	public function getActive(): array {
		return $this->select()->where('active', 1)->fetchAll();
	}

}