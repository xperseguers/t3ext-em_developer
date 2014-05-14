<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Extensionmanager\\ViewHelpers\\ProcessAvailableActionsViewHelper',
	'processActions',
	'Causal\\EmDeveloper\\Slots\\ExtensionManager',
	'processActions'
);
