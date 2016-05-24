<?php
namespace Evoweb\SfRegister\Controller;
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-13 Sebastian Fischer <typo3@evoweb.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * An frontend user controller
 */
class FeuserController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
	/**
	 * User repository
	 *
	 * @var \Evoweb\SfRegister\Domain\Repository\FrontendUserRepository
	 * @inject
	 */
	protected $userRepository = NULL;

	/**
	 * File service
	 *
	 * @var \Evoweb\SfRegister\Services\File
	 */
	protected $fileService;

	/**
	 * Signal slot dispatcher
	 *
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * The current view, as resolved by resolveView()
	 *
	 * @var \TYPO3\CMS\Fluid\View\TemplateView
	 * @api
	 */
	protected $view;

	/**
	 * Proxy action
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @return void
	 * @validate $user Evoweb.SfRegister:User
	 */
	public function proxyAction(\Evoweb\SfRegister\Domain\Model\FrontendUser $user) {
		$action = 'save';

		if ($this->request->hasArgument('form')) {
			$action = 'form';
		}

		$this->forward($action);
	}

	/**
	 * Disable Flashmessages
	 *
	 * @return boolean
	 */
	protected function getErrorFlashMessage() {
		return FALSE;
	}

	/**
	 * Initialize all actions
	 *
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
	 * @return void
	 */
	protected function initializeAction() {
		$this->fileService = $this->objectManager->get('Evoweb\\SfRegister\\Services\\File');

		if ($this->request->getControllerActionName() != 'removeImage' &&
				$this->request->hasArgument('removeImage') &&
				$this->request->getArgument('removeImage')) {
			$this->forward('removeImage');
		}
	}

	/**
	 * Inject an view object to be able to set templateRootPath from flexform
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
	 * @return void
	 */
	protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		if (isset($this->settings['templateRootPath']) && !empty($this->settings['templateRootPath'])) {
			$templateRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['templateRootPath'], TRUE);
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($templateRootPath)) {
				$this->view->setTemplateRootPath($templateRootPath);
			}
		}
	}


	/**
	 * Remove an image and forward to the action where it was called
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @param string $imagefile
	 * @return void
	 * @ignorevalidation $user
	 */
	protected function removeImageAction(\Evoweb\SfRegister\Domain\Model\FrontendUser $user, $imagefile) {
		if ($this->fileIsTemporary()) {
			$removedImage = $this->fileService->removeTemporaryFile($imagefile);
		} else {
			$removedImage = $this->fileService->removeUploadedImage($imagefile);
		}

		$user = $this->removeImageFromUserAndRequest($user, $removedImage);
			// @TODO can this get removed? testing
		$user->removeImage($removedImage);

		$requestUser = $this->request->getArgument('user');
		$requestUser['image'] = $user->getImage();
		$this->request->setArgument('user', $requestUser);

		$this->request->setArgument('removeImage', FALSE);

		$referrer = $this->request->getReferringRequest();
		if ($referrer !== NULL) {
			$this->forward($referrer->getControllerActionName(), $referrer->getControllerName(), $referrer->getControllerExtensionName(), $this->request->getArguments());
		}
	}

	/**
	 * Check if a file is only temporary uploaded
	 *
	 * @return boolean
	 */
	protected function fileIsTemporary() {
		$result = FALSE;

		if ($this->request->hasArgument('temporary') && $this->request->getArgument('temporary') != '') {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Remove an image from user object and request object
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @param string $removeImage
	 * @return \Evoweb\SfRegister\Domain\Model\FrontendUser
	 */
	protected function removeImageFromUserAndRequest(\Evoweb\SfRegister\Domain\Model\FrontendUser $user, $removeImage) {
		if ($user->getUid() !== NULL) {
			$localUser = $this->userRepository->findByUid($user->getUid());
			$localUser->removeImage($removeImage);
			$this->userRepository->update($localUser);

			$this->persistAll();
		}

		$user->removeImage($removeImage);

		$requestUser = $this->request->getArgument('user');
		$requestUser['image'] = $user->getImage();
		$this->request->setArgument('user', $requestUser);

		return $user;
	}

	/**
	 * Move uploaded image and add to user
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @return \Evoweb\SfRegister\Domain\Model\FrontendUser
	 */
	protected function moveTempFile($user) {
		if ($imagePath = $this->fileService->moveTempFileToTempFolder()) {
			$user->addImage($imagePath);
		}

		return $user;
	}

	/**
	 * Move uploaded image and add to user
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @return \Evoweb\SfRegister\Domain\Model\FrontendUser
	 */
	protected function moveImageFile($user) {
		$oldFilename = $user->getImage();

		$this->fileService->moveFileFromTempFolderToUploadFolder($oldFilename);

		$user->setImage($oldFilename);

		return $user;
	}

	/**
	 * Encrypt the password
	 *
	 * @param string $password
	 * @param array $settings
	 * @return string
	 */
	public static function encryptPassword($password, $settings) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('saltedpasswords') &&
				\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('FE')) {
			$saltObject = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL);
			if (is_object($saltObject)) {
				$password = $saltObject->getHashedPassword($password);
			}
		} elseif ($settings['encryptPassword'] === 'md5') {
			$password = md5($password);
		} elseif ($settings['encryptPassword'] === 'sha1') {
			$password = sha1($password);
		}

		return $password;
	}

	/**
	 * Persist all data that was not stored by now
	 *
	 * @return void
	 */
	protected function persistAll() {
		$this->objectManager
			->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')
			->persistAll();
	}

	/**
	 * Redirect to a page with given id
	 *
	 * @param integer $pageId
	 * @return void
	 */
	protected function redirectToPage($pageId) {
		$url = $this->uriBuilder
			->setTargetPageUid($pageId)
			->build();
		$this->redirectToUri($url);
	}

	/**
	 * Send emails to user and/or to admin
	 *
	 * @param \Evoweb\SfRegister\Domain\Model\FrontendUser $user
	 * @param string $type
	 * @return \Evoweb\SfRegister\Domain\Model\FrontendUser
	 */
	protected function sendEmails($user, $type) {
		/** @var $mailService \Evoweb\SfRegister\Services\Mail */
		$mailService = $this->objectManager->get('Evoweb\\SfRegister\\Services\\Mail');

		if ($this->isNotifyAdmin($type)) {
			$user = $mailService->sendAdminNotification($user, $type);
		}

		if ($this->isNotifyUser($type)) {
			$user = $mailService->sendUserNotification($user, $type);
		}

		return $user;
	}

	/**
	 * Check if the admin need to activate the account
	 *
	 * @param string $type
	 * @return boolean
	 */
	protected function isNotifyAdmin($type) {
		$result = FALSE;

		if ($this->settings['notifyAdmin' . $type]) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Check if the user need to activate the account
	 *
	 * @param string $type
	 * @return boolean
	 */
	protected function isNotifyUser($type) {
		$result = FALSE;

		if ($this->settings['notifyUser' . $type]) {
			$result = TRUE;
		}

		return $result;
	}
}

?>