<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
	$modx =& $transport->xpdo;

	$events = [
		"dartCRMAMODealAddBefore"
	];

	switch ($options[xPDOTransport::PACKAGE_ACTION]) {
		case xPDOTransport::ACTION_INSTALL:
		case xPDOTransport::ACTION_UPGRADE:
			foreach ($events as $eventName) {
				$event = $modx->getObject('modEvent', ['name' => $eventName]);
				if (!$event) {
					$event = $modx->newObject('modEvent');
					$event->set('name', $eventName);
					$event->set('service', 6);
					$event->set('groupname', 'dartcrm');
					$event->save();
				}
			}

			break;
		case xPDOTransport::ACTION_UNINSTALL:
			foreach ($events as $eventName) {
				$event = $modx->getObject('modEvent', ['name' => $eventName]);
				if ($event) {
					$event->remove();
				}
			}

			break;
	}
}
return true;