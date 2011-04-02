<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sebastian Fischer <typo3@evoweb.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A Password again validator
 *
 * @scope singleton
 */
class Tx_SfRegister_Domain_Validator_PasswordAgainValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {
	/**
	 * If the given passwords are valid
	 *
	 * @param array $passwordAgain The repeated password
	 * @return boolean
	 */
	public function isValid($passwordAgain) {
		$result = TRUE;
// @todo needs to be changed to the new password model
		if ($passwordAgain === '') {
			$this->addError(Tx_Extbase_Utility_Localization::translate('error.empty.password2', 'SfRegister'), 1296591065);
			$result = FALSE;
		} elseif ($passwordAgain !== $this->getPasswordFromRequest()) {
			$this->addError(Tx_Extbase_Utility_Localization::translate('error.notequal.passwords', 'SfRegister'), 1296591066);
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * Get password from request
	 *
	 * @return string
	 */
	protected function getPasswordFromRequest() {
		$requestData = t3lib_div::_GP('tx_sfregister_form');
		$result = '';

		if (isset($requestData['user'])) {
			$formData = $requestData['user'];
		} elseif (isset($requestData['password'])) {
			$formData = $requestData['password'];
		}
		if (isset($formData['password'])) {
			$result = $formData['password'];
		}

		return $result;
	}
}
?>
